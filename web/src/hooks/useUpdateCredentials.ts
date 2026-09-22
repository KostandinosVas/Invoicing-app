import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';

type UpdateCredentialsInput = {
  companyId: number;
  mydata_user_id: string;
  mydata_subscription_key: string;
};

export function useUpdateCredentials() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ companyId, ...payload }: UpdateCredentialsInput) => {
      await api.put(`/api/companies/${companyId}/credentials`, payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] });
    },
  });
}