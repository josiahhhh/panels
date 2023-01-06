import React, { forwardRef } from 'react';
import { Field as FormikField, FieldProps } from 'formik';
import { inputStyle } from '@/components/elements/Input';
import styled from 'styled-components/macro';
import tw from 'twin.macro';

interface OwnProps {
    name: string;
    light?: boolean;
    label?: string;
    description?: string;
    validate?: (value: any) => undefined | string | Promise<any>;
}

type Props = OwnProps & Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name'>;

export interface InputProps {
    hasError?: boolean;
}

const Input = styled.input<InputProps>`
    &:not([type='checkbox']):not([type='radio']) {
        ${inputStyle};

        border: 1px solid #ffffff;
        border-radius: 5px;
        background: transparent;
    }
`;

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`block text-sm text-neutral-50`};

    display: inline-block;
    position: absolute;

    padding: 0 0.25em;

    margin-top: -0.65rem;
    margin-left: 1rem;

    background: #0e101f;
    z-index: 20;
`;

const Field = forwardRef<HTMLInputElement, Props>(({ id, name, light = false, label, description, validate, ...props }, ref) => (
    <FormikField innerRef={ref} name={name} validate={validate}>
        {({ field, form: { errors, touched } }: FieldProps) => (
            <>
                {label && (
                    <Label htmlFor={id} isLight={light}>
                        {label}
                    </Label>
                )}
                <Input id={id} {...field} {...props} hasError={!!(touched[field.name] && errors[field.name])} />
                {touched[field.name] && errors[field.name] ? (
                    <p className={'input-help error'}>{(errors[field.name] as string).charAt(0).toUpperCase() + (errors[field.name] as string).slice(1)}</p>
                ) : description ? (
                    <p className={'input-help'}>{description}</p>
                ) : null}
            </>
        )}
    </FormikField>
));
Field.displayName = 'Field';

export default Field;
