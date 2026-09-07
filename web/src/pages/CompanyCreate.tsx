import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useCreateCompany } from '../hooks/useCreateCompany';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import styles from './CompanyCreate.module.css';

const schema = z.object({
  name: z.string().min(1, 'Η επωνυμία είναι υποχρεωτική'),
  vat_number: z.string().regex(/^\d{9}$/, 'Το ΑΦΜ πρέπει να έχει 9 ψηφία'),
  tax_office: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  postal_code: z.string().optional(),
});

type FormValues = z.infer<typeof schema>;

export function CompanyCreate() {
  const navigate = useNavigate();
  const createCompany = useCreateCompany();

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  async function onSubmit(values: FormValues) {
    try {
      await createCompany.mutateAsync(values);
      navigate('/companies');
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
        title="Νέα εταιρεία"
        subtitle="Τα στοιχεία θα εμφανίζονται στα παραστατικά που εκδίδει."
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <Field
          label="Επωνυμία"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className={styles.row}>
          <Field
            label="ΑΦΜ"
            hint="9 ψηφία"
            error={errors.vat_number?.message}
            inputMode="numeric"
            {...register('vat_number')}
          />

          <Field
            label="ΔΟΥ"
            error={errors.tax_office?.message}
            {...register('tax_office')}
          />
        </div>

        <Field
          label="Διεύθυνση"
          error={errors.address?.message}
          {...register('address')}
        />

        <div className={styles.row}>
          <Field
            label="Πόλη"
            error={errors.city?.message}
            {...register('city')}
          />

          <Field
            label="Ταχυδρομικός κώδικας"
            error={errors.postal_code?.message}
            inputMode="numeric"
            {...register('postal_code')}
          />
        </div>

        <div className={styles.actions}>
          <Button
            type="button"
            variant="secondary"
            onClick={() => navigate('/companies')}
          >
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