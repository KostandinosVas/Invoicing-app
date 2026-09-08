import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { DocumentType, Series } from '../types/api';

export type CreateSeriesInput = {
  company_id: number;
  code: string;
  document_type: DocumentType;
};

export function useCreateSeries() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: CreateSeriesInput) => {
      const res = await api.post<{ data: Series }>('/api/series', input);
      return res.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['series'] });
    },
  });
}