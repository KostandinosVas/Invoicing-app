import { useFieldArray, useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useState } from 'react';
import { useInvoice } from '../hooks/useInvoices';
import { useSeries } from '../hooks/useSeries';
import { useCreateCreditNote } from '../hooks/useCreateCreditNote';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import { Select } from '../components/Select';
import { formatCents, formatEurosInput, parseEuros } from '../lib/money';
import { calculateLine, sumTotals } from '../lib/calculations';
import styles from './InvoiceCreate.module.css';

const VAT_RATES = [24, 13, 6, 0];

const CLASSIFICATIONS = [
  { value: 'goods_sale', label: 'Πώληση εμπορευμάτων' },
  { value: 'services_provision', label: 'Παροχή υπηρεσιών' },
];

const lineSchema = z.object({
  description: z.string().min(1, 'Υποχρεωτικό'),
  quantity: z.string().regex(/^\d+([.,]\d{1,3})?$/, 'Μη έγκυρη'),
  unit_price: z.string().refine((v) => parseEuros(v) !== null, 'Μη έγκυρη'),
  vat_rate: z.string(),
  income_classification: z.string(),
});

const schema = z.object({
  series_id: z.string().min(1, 'Επιλέξτε σειρά'),
  issue_date: z.string().min(1, 'Υποχρεωτικό'),
  lines: z.array(lineSchema).min(1, 'Απαιτείται τουλάχιστον μία γραμμή'),
});

type FormValues = z.infer<typeof schema>;

export function CreditNoteCreate() {
  const { id } = useParams<{ id: string }>();
  const invoiceId = Number(id);
  const navigate = useNavigate();

  const { data: invoice, isLoading } = useInvoice(invoiceId);
  const createCreditNote = useCreateCreditNote();
  const [formError, setFormError] = useState<string | null>(null);

  const { data: seriesData } = useSeries({
    companyId: invoice?.company_id,
    documentType: 'credit_note',
  });

  const series = seriesData?.data ?? [];

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      series_id: '',
      issue_date: new Date().toISOString().slice(0, 10),
      lines: [
        {
          description: '',
          quantity: '1',
          unit_price: '0,00',
          vat_rate: '24',
          income_classification: 'services_provision',
        },
      ],
    },
  });

  const { fields, append, remove } = useFieldArray({ control, name: 'lines' });
  const watchedLines = useWatch({ control, name: 'lines' });

  const lineTotals = (watchedLines ?? []).map((line) =>
    calculateLine({
      quantity: line?.quantity ?? '0',
      unitPriceCents: parseEuros(line?.unit_price ?? '0') ?? 0,
      vatRate: Number(line?.vat_rate ?? 0),
    }),
  );

  const totals = sumTotals(lineTotals);

  if (isLoading) return <p>Φόρτωση…</p>;

  if (!invoice) return <p>Το παραστατικό δεν βρέθηκε.</p>;

  async function onSubmit(values: FormValues) {
    setFormError(null);

    try {
      const creditNote = await createCreditNote.mutateAsync({
        invoiceId,
        series_id: Number(values.series_id),
        issue_date: values.issue_date,
        lines: values.lines.map((line) => ({
          description: line.description,
          quantity: line.quantity.replace(',', '.'),
          unit_price_cents: parseEuros(line.unit_price) ?? 0,
          vat_rate: Number(line.vat_rate),
          income_classification: line.income_classification,
        })),
      });

      navigate(`/invoices/${creditNote.id}`);
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
        title="Νέο πιστωτικό"
        subtitle={`Διόρθωση του παραστατικού #${invoice.number} — ${invoice.customer.name}. Αποθηκεύεται ως προσχέδιο.`}
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <section className={styles.card}>
          <h2 className={styles.cardTitle}>Στοιχεία πιστωτικού</h2>

          <div className={styles.headerGrid}>
            <Select
              label="Σειρά πιστωτικών"
              placeholder="Επιλέξτε…"
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
            <span>Περιγραφή</span>
            <span>Χαρακτηρισμός</span>
            <span>Ποσότητα</span>
            <span>Τιμή (€)</span>
            <span>ΦΠΑ</span>
            <span>Σύνολο</span>
            <span />
          </div>

          <div className={styles.lines}>
            {fields.map((field, index) => (
              <div key={field.id} className={styles.lineRow}>
                <Field
                  label=""
                  error={errors.lines?.[index]?.description?.message}
                  {...register(`lines.${index}.description`)}
                />

                <Select
                  label=""
                  options={CLASSIFICATIONS}
                  {...register(`lines.${index}.income_classification`)}
                />

                <Field
                  label=""
                  inputMode="decimal"
                  error={errors.lines?.[index]?.quantity?.message}
                  {...register(`lines.${index}.quantity`)}
                />

                <Field
                  label=""
                  inputMode="decimal"
                  error={errors.lines?.[index]?.unit_price?.message}
                  {...register(`lines.${index}.unit_price`)}
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
            <Button
              type="button"
              variant="secondary"
              size="small"
              onClick={() =>
                append({
                  description: '',
                  quantity: '1',
                  unit_price: '0,00',
                  vat_rate: '24',
                  income_classification: 'services_provision',
                })
              }
            >
              Προσθήκη γραμμής
            </Button>
          </div>
        </section>

        <section className={styles.card}>
          <div className={styles.totals}>
            <div className={styles.totalRow}>
              <span className={styles.totalLabel}>Καθαρή αξία</span>
              <span className={styles.totalValue}>{formatCents(totals.netAmountCents)}</span>
            </div>

            <div className={styles.totalRow}>
              <span className={styles.totalLabel}>ΦΠΑ</span>
              <span className={styles.totalValue}>{formatCents(totals.vatAmountCents)}</span>
            </div>

            <div className={`${styles.totalRow} ${styles.grandTotal}`}>
              <span>Σύνολο πιστωτικού</span>
              <span className={styles.totalValue}>{formatCents(totals.totalCents)}</span>
            </div>

            <div className={styles.totalRow}>
              <span className={styles.totalLabel}>Αρχικό παραστατικό</span>
              <span className={styles.totalValue}>
                {formatCents(invoice.totals.total_cents)}
              </span>
            </div>
          </div>
        </section>

        {formError && <p className={styles.formError}>{formError}</p>}

        <div className={styles.actions}>
          <Button
            type="button"
            variant="secondary"
            onClick={() => navigate(`/invoices/${invoiceId}`)}
          >
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