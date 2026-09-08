import { useFieldArray, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useState } from 'react';
import { useCreateInvoice } from '../hooks/useCreateInvoice';
import { useCompanies } from '../hooks/useCompanies';
import { useCustomers } from '../hooks/useCustomers';
import { useItems } from '../hooks/useItems';
import { useSeries } from '../hooks/useSeries';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import { Select } from '../components/Select';
import { formatCents } from '../lib/money';
import { calculateLine, sumTotals } from '../lib/calculations';
import type { DocumentType } from '../types/api';
import styles from './InvoiceCreate.module.css';

const VAT_RATES = [24, 13, 6, 0];

const lineSchema = z.object({
  item_id: z.string(),
  description: z.string().min(1, 'Υποχρεωτικό'),
  quantity: z.string().regex(/^\d+([.,]\d{1,3})?$/, 'Μη έγκυρη'),
  unit_price_cents: z.string().regex(/^\d+$/, 'Μη έγκυρη'),
  vat_rate: z.string(),
});

const schema = z.object({
  company_id: z.string().min(1, 'Επιλέξτε εταιρεία'),
  customer_id: z.string().min(1, 'Επιλέξτε πελάτη'),
  series_id: z.string().min(1, 'Επιλέξτε σειρά'),
  issue_date: z.string().min(1, 'Υποχρεωτικό'),
  lines: z.array(lineSchema).min(1, 'Απαιτείται τουλάχιστον μία γραμμή'),
});

type FormValues = z.infer<typeof schema>;

const emptyLine = {
  item_id: '',
  description: '',
  quantity: '1',
  unit_price_cents: '0',
  vat_rate: '24',
};

export function InvoiceCreate() {
  const navigate = useNavigate();
  const createInvoice = useCreateInvoice();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    control,
    handleSubmit,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      company_id: '',
      customer_id: '',
      series_id: '',
      issue_date: new Date().toISOString().slice(0, 10),
      lines: [emptyLine],
    },
  });

  const { fields, append, remove } = useFieldArray({ control, name: 'lines' });

  const companyId = useWatch({ control, name: 'company_id' });
  const watchedLines = useWatch({ control, name: 'lines' });

  const numericCompanyId = companyId ? Number(companyId) : undefined;

  const { data: companiesData } = useCompanies();
  const { data: customersData } = useCustomers(numericCompanyId);
  const { data: itemsData } = useItems({
    companyId: numericCompanyId,
    activeOnly: true,
  });
  const { data: seriesData } = useSeries({
    companyId: numericCompanyId,
    documentType: 'invoice',
  });

  const companies = companiesData?.data ?? [];
  const customers = customersData?.data ?? [];
  const items = itemsData?.data ?? [];
  const series = seriesData?.data ?? [];

  const lineTotals = (watchedLines ?? []).map((line) =>
    calculateLine({
      quantity: line?.quantity ?? '0',
      unitPriceCents: Number(line?.unit_price_cents ?? 0),
      vatRate: Number(line?.vat_rate ?? 0),
    }),
  );

  const totals = sumTotals(lineTotals);

  function applyItem(index: number, itemId: string) {
    const item = items.find((i) => String(i.id) === itemId);
    if (!item) return;

    setValue(`lines.${index}.description`, item.name);
    setValue(`lines.${index}.unit_price_cents`, String(item.unit_price_cents));
    setValue(`lines.${index}.vat_rate`, String(item.vat_rate));
  }

  function onCompanyChange() {
    setValue('customer_id', '');
    setValue('series_id', '');
  }

  async function onSubmit(values: FormValues) {
    setFormError(null);

    try {
      const invoice = await createInvoice.mutateAsync({
        company_id: Number(values.company_id),
        customer_id: Number(values.customer_id),
        series_id: Number(values.series_id),
        document_type: 'invoice' as DocumentType,
        issue_date: values.issue_date,
        lines: values.lines.map((line) => ({
          item_id: line.item_id ? Number(line.item_id) : undefined,
          description: line.description,
          quantity: line.quantity.replace(',', '.'),
          unit_price_cents: Number(line.unit_price_cents),
          vat_rate: Number(line.vat_rate),
        })),
      });

      navigate(`/invoices/${invoice.id}`);
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 422) {
        const serverErrors = error.response.data.errors as Record<string, string[]>;

        Object.entries(serverErrors).forEach(([field, messages]) => {
          setError(field as never, { message: messages[0] });
        });

        setFormError('Ελέγξτε τα πεδία με σφάλμα.');
      } else {
        setFormError('Δεν ήταν δυνατή η αποθήκευση.');
      }
    }
  }

  return (
    <>
      <PageHeader
        title="Νέο παραστατικό"
        subtitle="Αποθηκεύεται ως προσχέδιο. Η έκδοση γίνεται σε δεύτερο βήμα."
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <section className={styles.card}>
          <h2 className={styles.cardTitle}>Στοιχεία παραστατικού</h2>

          <div className={styles.headerGrid}>
            <Select
              label="Εταιρεία"
              placeholder="Επιλέξτε…"
              options={companies.map((c) => ({ value: c.id, label: c.name }))}
              error={errors.company_id?.message}
              {...register('company_id', { onChange: onCompanyChange })}
            />

            <Select
              label="Πελάτης"
              placeholder={companyId ? 'Επιλέξτε…' : 'Επιλέξτε πρώτα εταιρεία'}
              disabled={!companyId}
              options={customers.map((c) => ({ value: c.id, label: c.name }))}
              error={errors.customer_id?.message}
              {...register('customer_id')}
            />

            <Select
              label="Σειρά"
              placeholder={companyId ? 'Επιλέξτε…' : 'Επιλέξτε πρώτα εταιρεία'}
              disabled={!companyId}
              options={series.map((s) => ({ value: s.id, label: s.code }))}
              error={errors.series_id?.message}
              {...register('series_id')}
            />

            <Field
              label="Ημερομηνία έκδοσης"
              type="date"
              error={errors.issue_date?.message}
              {...register('issue_date')}
            />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.cardTitle}>Γραμμές</h2>

          <div className={styles.lineHeader}>
            <span>Είδος</span>
            <span>Περιγραφή</span>
            <span>Ποσότητα</span>
            <span>Τιμή (λεπτά)</span>
            <span>ΦΠΑ</span>
            <span>Σύνολο</span>
            <span />
          </div>

          <div className={styles.lines}>
            {fields.map((field, index) => (
              <div key={field.id} className={styles.lineRow}>
                <Select
                  label=""
                  placeholder="Ελεύθερη"
                  disabled={!companyId}
                  options={items.map((i) => ({ value: i.id, label: i.name }))}
                  {...register(`lines.${index}.item_id`, {
                    onChange: (e) => applyItem(index, e.target.value),
                  })}
                />

                <Field
                  label=""
                  error={errors.lines?.[index]?.description?.message}
                  {...register(`lines.${index}.description`)}
                />

                <Field
                  label=""
                  inputMode="decimal"
                  error={errors.lines?.[index]?.quantity?.message}
                  {...register(`lines.${index}.quantity`)}
                />

                <Field
                  label=""
                  inputMode="numeric"
                  error={errors.lines?.[index]?.unit_price_cents?.message}
                  {...register(`lines.${index}.unit_price_cents`)}
                />

                <Select
                  label=""
                  options={VAT_RATES.map((r) => ({ value: r, label: `${r}%` }))}
                  {...register(`lines.${index}.vat_rate`)}
                />

                <span className={styles.lineTotal}>
                  {formatCents(lineTotals[index]?.totalCents ?? 0)}
                </span>

                <span className={styles.removeCell}>
                  <Button
                    type="button"
                    variant="secondary"
                    size="small"
                    disabled={fields.length === 1}
                    onClick={() => remove(index)}
                  >
                    ✕
                  </Button>
                </span>
              </div>
            ))}
          </div>

          <div className={styles.addLine}>
            <Button type="button" variant="secondary" size="small" onClick={() => append(emptyLine)}>
              Προσθήκη γραμμής
            </Button>
          </div>
        </section>

        <section className={styles.card}>
          <div className={styles.totals}>
            <div className={styles.totalRow}>
              <span className={styles.totalLabel}>Καθαρή αξία</span>
              <span className={styles.totalValue}>
                {formatCents(totals.netAmountCents)}
              </span>
            </div>

            <div className={styles.totalRow}>
              <span className={styles.totalLabel}>ΦΠΑ</span>
              <span className={styles.totalValue}>
                {formatCents(totals.vatAmountCents)}
              </span>
            </div>

            <div className={`${styles.totalRow} ${styles.grandTotal}`}>
              <span>Σύνολο</span>
              <span className={styles.totalValue}>
                {formatCents(totals.totalCents)}
              </span>
            </div>
          </div>
        </section>

        {formError && <p className={styles.formError}>{formError}</p>}

        <div className={styles.actions}>
          <Button type="button" variant="secondary" onClick={() => navigate('/invoices')}>
            Ακύρωση
          </Button>

          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Αποθήκευση…' : 'Αποθήκευση προσχεδίου'}
          </Button>
        </div>
      </form>
    </>
  );
}