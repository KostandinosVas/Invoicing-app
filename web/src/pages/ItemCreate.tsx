import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useCreateItem } from '../hooks/useCreateItem';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import { Select } from '../components/Select';
import { parseEuros } from '../lib/money';
import styles from './CompanyCreate.module.css';

const VAT_RATES = [24, 13, 6, 0];

const schema = z.object({
  company_id: z.string().min(1, 'Επιλέξτε εταιρεία'),
  name: z.string().min(1, 'Η περιγραφή είναι υποχρεωτική'),
  code: z.string().optional(),
  unit: z.string().optional(),
  price: z
    .string()
    .min(1, 'Η τιμή είναι υποχρεωτική')
    .refine((v) => parseEuros(v) !== null, 'Μη έγκυρη τιμή'),
  vat_rate: z.string().min(1, 'Επιλέξτε συντελεστή'),
});

type FormValues = z.infer<typeof schema>;

export function ItemCreate() {
  const navigate = useNavigate();
  const createItem = useCreateItem();
  const { data: companiesData } = useCompanies();

  const companies = companiesData?.data ?? [];

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { unit: 'τεμ', vat_rate: '24' },
  });

  async function onSubmit(values: FormValues) {
    const cents = parseEuros(values.price);

    if (cents === null) {
      setError('price', { message: 'Μη έγκυρη τιμή' });
      return;
    }

    try {
      await createItem.mutateAsync({
        company_id: Number(values.company_id),
        name: values.name,
        code: values.code || undefined,
        unit: values.unit || undefined,
        unit_price_cents: cents,
        vat_rate: Number(values.vat_rate),
      });
      navigate('/items');
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 422) {
        const serverErrors = error.response.data.errors as Record<string, string[]>;

        Object.entries(serverErrors).forEach(([field, messages]) => {
          const target = field === 'unit_price_cents' ? 'price' : field;
          setError(target as keyof FormValues, { message: messages[0] });
        });
      }
    }
  }

  return (
    <>
      <PageHeader
        title="Νέο είδος"
        subtitle="Τα στοιχεία αντιγράφονται στη γραμμή του παραστατικού."
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <Select
          label="Εταιρεία"
          placeholder="Επιλέξτε…"
          options={companies.map((c) => ({ value: c.id, label: c.name }))}
          error={errors.company_id?.message}
          {...register('company_id')}
        />

        <Field label="Περιγραφή" error={errors.name?.message} {...register('name')} />

        <div className={styles.row}>
          <Field
            label="Κωδικός"
            hint="Προαιρετικός"
            error={errors.code?.message}
            {...register('code')}
          />

          <Field
            label="Μονάδα μέτρησης"
            error={errors.unit?.message}
            {...register('unit')}
          />
        </div>

        <div className={styles.row}>
          <Field
            label="Τιμή μονάδας (€)"
            hint="π.χ. 12,50"
            error={errors.price?.message}
            inputMode="decimal"
            {...register('price')}
          />

          <Select
            label="Συντελεστής ΦΠΑ"
            options={VAT_RATES.map((r) => ({ value: r, label: `${r}%` }))}
            error={errors.vat_rate?.message}
            {...register('vat_rate')}
          />
        </div>

        <div className={styles.actions}>
          <Button type="button" variant="secondary" onClick={() => navigate('/items')}>
            Ακύρωση
          </Button>

          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Αποθήκευση…' : 'Αποθήκευση'}
          </Button>
        </div>
      </form>
    </>
  );
}