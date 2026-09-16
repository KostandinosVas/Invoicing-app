import { useMutation } from '@tanstack/react-query';
import { api } from '../lib/api';

export function useEmailInvoice() {
  return useMutation({
    mutationFn: async ({ id, email }: { id: number; email: string }) => {
      await api.post(`/api/invoices/${id}/email`, { email });
    },
  });
}