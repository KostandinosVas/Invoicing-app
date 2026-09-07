import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { Company } from '../types/api';

export type CreateCompanyInput = {
  name: string;
  vat_number: string;
  tax_office?: string;
  address?: string;
  city?: string;
  postal_code?: string;
};

export function useCreateCompany() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateCompanyInput) => {
      const res = await api.post<{ data: Company }>('/api/companies', input);
      return res.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });
}