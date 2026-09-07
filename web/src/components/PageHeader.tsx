import type { ReactNode } from 'react';
import styles from './PageHeader.module.css';

type Props = {
  title: string;
  subtitle?: string;
  action?: ReactNode;
};

export function PageHeader({ title, subtitle, action }: Props) {
  return (
    <header className={styles.header}>
      <div className={styles.titleGroup}>
        <h1>{title}</h1>
        {subtitle && <p className={styles.subtitle}>{subtitle}</p>}
      </div>
      {action}
    </header>
  );
}