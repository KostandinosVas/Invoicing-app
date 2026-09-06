import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useState } from 'react';
import { useAuth } from '../auth/AuthContext';

const schema = z.object({
  email: z.string().email('Μη έγκυρο email'),
  password: z.string().min(1, 'Απαιτείται συνθηματικό'),
});

type FormValues = z.infer<typeof schema>;

export function Login() {
  const { login } = useAuth();
  const [serverError, setServerError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) });

  async function onSubmit(values: FormValues) {
    setServerError(null);
    try {
      await login(values.email, values.password);
    } catch {
      setServerError('Τα στοιχεία σύνδεσης δεν είναι σωστά.');
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <h1>Σύνδεση</h1>

      <label htmlFor="email">Email</label>
      <input id="email" type="email" {...register('email')} />
      {errors.email && <p>{errors.email.message}</p>}

      <label htmlFor="password">Συνθηματικό</label>
      <input id="password" type="password" {...register('password')} />
      {errors.password && <p>{errors.password.message}</p>}

      {serverError && <p>{serverError}</p>}

      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? 'Σύνδεση…' : 'Σύνδεση'}
      </button>
    </form>
  );
}