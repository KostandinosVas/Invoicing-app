import type { ButtonHTMLAttributes } from 'react';
import styles from './Button.module.css';

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary' | 'danger';
  size?: 'default' | 'small';
};

export function Button({
  variant = 'primary',
  size = 'default',
  className,
  ...rest
}: Props) {
  const classes = [
    styles.button,
    styles[variant],
    size === 'small' ? styles.small : '',
    className ?? '',
  ]
    .filter(Boolean)
    .join(' ');

  return <button className={classes} {...rest} />;
}