import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Invoice } from '../types/api';

export function useSubmitInvoice() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const res = await api.post<{ data: Invoice }>(`/api/invoices/${id}/submit`);
      return res.data.data;
    },
    onSuccess: (invoice) => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.setQueryData(['invoices', 'detail', invoice.id], invoice);
    },
  });
}