import type { DocumentType } from '../types/api';

export const documentTypeLabels: Record<DocumentType, string> = {
  invoice: 'Τιμολόγιο',
  credit_note: 'Πιστωτικό',
  cancellation: 'Ακυρωτικό',
};

export const documentTypeOptions = Object.entries(documentTypeLabels).map(
  ([value, label]) => ({ value, label }),
);