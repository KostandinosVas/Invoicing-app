export type Company = {
  id: number;
  name: string;
  vat_number: string;
  tax_office: string | null;
  address: string | null;
  city: string | null;
  postal_code: string | null;
  created_at: string | null;
};

export type Paginated<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};