import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Invoice } from '../types/api';

export type CreateCreditNoteInput = {
  invoiceId: number;
  series_id: number;
  issue_date: string;
  lines: Array<{
    description: string;
    unit?: string;
    quantity: string;
    unit_price_cents: number;
    vat_rate: number;
    income_classification: string;
  }>;
};

export function useCreateCreditNote() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ invoiceId, ...payload }: CreateCreditNoteInput) => {
      const res = await api.post<{ data: Invoice }>(
        `/api/invoices/${invoiceId}/credit-notes`,
        payload,
      );
      return res.data.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
      queryClient.invalidateQueries({
        queryKey: ['invoices', 'detail', variables.invoiceId],
      });
    },
  });
}