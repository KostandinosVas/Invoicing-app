import type { InvoiceStatus } from '../types/api';
import styles from './StatusBadge.module.css';

const labels: Record<InvoiceStatus, string> = {
  draft: 'Προσχέδιο',
  issued: 'Εκδοθέν',
  submitting: 'Υποβάλλεται',
  submitted: 'Υποβληθέν',
  rejected: 'Απορρίφθηκε',
  cancelled: 'Ακυρωμένο',
};

export function StatusBadge({ status }: { status: InvoiceStatus }) {
  return (
    <span className={`${styles.badge} ${styles[status]}`}>
      {labels[status]}
    </span>
  );
}