import { forwardRef, useId } from 'react';
import type { InputHTMLAttributes } from 'react';
import styles from './Field.module.css';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
  hint?: string;
};

export const Field = forwardRef<HTMLInputElement, Props>(function Field(
  { label, error, hint, id, ...rest },
  ref,
) {
  const generatedId = useId();
  const inputId = id ?? generatedId;
  const errorId = `${inputId}-error`;

  return (
    <div className={styles.field}>
      <label className={styles.label} htmlFor={inputId}>
        {label}
      </label>

      <input
        id={inputId}
        ref={ref}
        className={styles.input}
        aria-invalid={error ? 'true' : undefined}
        aria-describedby={error ? errorId : undefined}
        {...rest}
      />

      {error && (
        <p id={errorId} className={styles.error}>
          {error}
        </p>
      )}

      {!error && hint && <p className={styles.hint}>{hint}</p>}
    </div>
  );
});