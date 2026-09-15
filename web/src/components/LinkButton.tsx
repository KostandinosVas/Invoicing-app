import { Link } from 'react-router-dom';
import type { ComponentProps } from 'react';
import styles from './Button.module.css';

type Props = ComponentProps<typeof Link> & {
  variant?: 'primary' | 'secondary' | 'danger';
  size?: 'default' | 'small';
};

export function LinkButton({
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

  return <Link className={classes} {...rest} />;
}