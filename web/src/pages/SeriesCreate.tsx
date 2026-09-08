import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useCreateSeries } from '../hooks/useCreateSeries';
import { useCompanies } from '../hooks/useCompanies';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import { Select } from '../components/Select';
import { documentTypeOptions } from '../lib/labels';
import type { DocumentType } from '../types/api';
import styles from './CompanyCreate.module.css';

const schema = z.object({
  company_id: z.string().min(1, 'Επιλέξτε εταιρεία'),
  code: z.string().min(1, 'Ο κωδικός είναι υποχρεωτικός').max(20),
  document_type: z.string().min(1, 'Επιλέξτε τύπο'),
});

type FormValues = z.infer<typeof schema>;

export function SeriesCreate() {
  const navigate = useNavigate();
  const createSeries = useCreateSeries();
  const { data: companiesData } = useCompanies();

  const companies = companiesData?.data ?? [];

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { document_type: 'invoice' },
  });

  async function onSubmit(values: FormValues) {
    try {
      await createSeries.mutateAsync({
        company_id: Number(values.company_id),
        code: values.code,
        document_type: values.document_type as DocumentType,
      });
      navigate('/series');
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 422) {
        const serverErrors = error.response.data.errors as Record<string, string[]>;

        Object.entries(serverErrors).forEach(([field, messages]) => {
          setError(field as keyof FormValues, { message: messages[0] });
        });
      }
    }
  }

  return (
    <>
      <PageHeader
        title="Νέα σειρά αρίθμησης"
        subtitle="Ο κωδικός είναι μοναδικός εντός εταιρείας. Η σειρά δεν διαγράφεται μετά τη δημιουργία."
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <Select
          label="Εταιρεία"
          placeholder="Επιλέξτε…"
          options={companies.map((c) => ({ value: c.id, label: c.name }))}
          error={errors.company_id?.message}
          {...register('company_id')}
        />

        <div className={styles.row}>
          <Field
            label="Κωδικός σειράς"
            hint="π.χ. ΤΙΜ, Α, 2026Α"
            error={errors.code?.message}
            {...register('code')}
          />

          <Select
            label="Τύπος παραστατικού"
            options={documentTypeOptions}
            error={errors.document_type?.message}
            {...register('document_type')}
          />
        </div>

        <div className={styles.actions}>
          <Button type="button" variant="secondary" onClick={() => navigate('/series')}>
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