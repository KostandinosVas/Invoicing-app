import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useNavigate } from 'react-router-dom';
import { isAxiosError } from 'axios';
import { useCreateCompany } from '../hooks/useCreateCompany';

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
    <form onSubmit={handleSubmit(onSubmit)}>
      <h2>Νέα εταιρεία</h2>

      <label htmlFor="name">Επωνυμία</label>
      <input id="name" {...register('name')} />
      {errors.name && <p>{errors.name.message}</p>}

      <label htmlFor="vat_number">ΑΦΜ</label>
      <input id="vat_number" {...register('vat_number')} />
      {errors.vat_number && <p>{errors.vat_number.message}</p>}

      <label htmlFor="tax_office">ΔΟΥ</label>
      <input id="tax_office" {...register('tax_office')} />

      <label htmlFor="address">Διεύθυνση</label>
      <input id="address" {...register('address')} />

      <label htmlFor="city">Πόλη</label>
      <input id="city" {...register('city')} />

      <label htmlFor="postal_code">ΤΚ</label>
      <input id="postal_code" {...register('postal_code')} />

      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? 'Αποθήκευση…' : 'Αποθήκευση'}
      </button>
    </form>
  );
}