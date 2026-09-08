import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import type { DocumentType, Paginated, Series } from '../types/api';

type Filters = {
  companyId?: number;
  documentType?: DocumentType;
};

export function useSeries({ companyId, documentType }: Filters = {}) {
  return useQuery({
    queryKey: ['series', companyId ?? null, documentType ?? null],
    queryFn: async () => {
      const res = await api.get<Paginated<Series>>('/api/series', {
        params: {
          ...(companyId ? { company_id: companyId } : {}),
          ...(documentType ? { document_type: documentType } : {}),
        },
      });
      return res.data;
    },
  });
}