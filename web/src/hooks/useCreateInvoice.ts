import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { DocumentType, Invoice } from '../types/api';

export type CreateInvoiceLineInput = {
  item_id?: number;
  description?: string;
  unit?: string;
  quantity: string;
  unit_price_cents?: number;
  vat_rate?: number;
};

export type CreateInvoiceInput = {
  company_id: number;
  customer_id: number;
  series_id: number;
  document_type: DocumentType;
  issue_date: string;
  lines: CreateInvoiceLineInput[];
};

export function useCreateInvoice() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateInvoiceInput) => {
      const res = await api.post<{ data: Invoice }>('/api/invoices', input);
      return res.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices'] });
    },
  });
}