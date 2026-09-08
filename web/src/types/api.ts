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

export type Customer = {
  id: number;
  company_id: number;
  name: string;
  vat_number: string | null;
  tax_office: string | null;
  address: string | null;
  city: string | null;
  postal_code: string | null;
  country: string;
};

export type Item = {
  id: number;
  company_id: number;
  code: string | null;
  name: string;
  unit: string;
  unit_price_cents: number;
  vat_rate: number;
  is_active: boolean;
};

export type DocumentType = 'invoice' | 'credit_note' | 'cancellation';

export type Series = {
  id: number;
  company_id: number;
  code: string;
  document_type: DocumentType;
  last_number: number;
  is_active: boolean;
};