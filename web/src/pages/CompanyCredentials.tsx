import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate, useParams } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useCompanies } from '../hooks/useCompanies';
import { useUpdateCredentials } from '../hooks/useUpdateCredentials';
import { useAuth } from '../auth/AuthContext';
import { PageHeader } from '../components/PageHeader';
import { Button } from '../components/Button';
import { Field } from '../components/Field';
import styles from './InvoiceCreate.module.css';

const schema = z.object({
  mydata_user_id: z.string().min(1, 'Υποχρεωτικό'),
  mydata_subscription_key: z.string().min(1, 'Υποχρεωτικό'),
});

type FormValues = z.infer<typeof schema>;

export function CompanyCredentials() {
  const { id } = useParams<{ id: string }>();
  const companyId = Number(id);
  const navigate = useNavigate();

  const { user } = useAuth();
  const { data, isLoading } = useCompanies();
  const updateCredentials = useUpdateCredentials();

  const [formError, setFormError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { mydata_user_id: '', mydata_subscription_key: '' },
  });

  if (!user?.can_manage_credentials) {
    return <p>Μόνο ο διαχειριστής μπορεί να διαχειριστεί τα διαπιστευτήρια myDATA.</p>;
  }

  if (isLoading) return <p>Φόρτωση…</p>;

  const company = data?.data.find((c) => c.id === companyId);

  if (!company) return <p>Η εταιρεία δεν βρέθηκε.</p>;

  async function onSubmit(values: FormValues) {
    setFormError(null);
    setSaved(false);

    try {
      await updateCredentials.mutateAsync({ companyId, ...values });
      reset();
      setSaved(true);
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 403) {
        setFormError('Δεν έχετε δικαίωμα αλλαγής διαπιστευτηρίων.');
      } else if (isAxiosError(error) && error.response?.status === 422) {
        setFormError('Ελέγξτε τα πεδία.');
      } else {
        setFormError('Η αποθήκευση απέτυχε.');
      }
    }
  }

  return (
    <>
      <PageHeader
        title="Διαπιστευτήρια myDATA"
        subtitle={`${company.name} · ${
          company.has_mydata_credentials
            ? 'Έχουν οριστεί. Η νέα καταχώρηση αντικαθιστά τα υπάρχοντα.'
            : 'Δεν έχουν οριστεί — η εταιρεία δεν μπορεί να διαβιβάσει.'
        }`}
      />

      <form className={styles.form} onSubmit={handleSubmit(onSubmit)}>
        <section className={styles.card}>
          <h2 className={styles.cardTitle}>Στοιχεία πρόσβασης ΑΑΔΕ</h2>

          <Field
            label="User ID (aade-user-id)"
            autoComplete="off"
            error={errors.mydata_user_id?.message}
            {...register('mydata_user_id')}
          />

          <Field
            label="Subscription key"
            type="password"
            autoComplete="new-password"
            error={errors.mydata_subscription_key?.message}
            {...register('mydata_subscription_key')}
          />
        </section>

        {formError && <p className={styles.formError}>{formError}</p>}

        {saved && <p>Τα διαπιστευτήρια αποθηκεύτηκαν.</p>}

        <div className={styles.actions}>
          <Button type="button" variant="secondary" onClick={() => navigate('/companies')}>
            Πίσω
          </Button>

          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Αποθήκευση…' : 'Αποθήκευση'}
          </Button>
        </div>
      </form>
    </>
  );
}