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

        border: 1px solid #283350;
        border-radius: 5px;
        background: transparent;
        color: #fff;
        padding: 0.5rem 0.75rem;
        
    }
`;

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`text-xs text-neutral-300`};
    font-weight: 600;
    display: block;
    margin-bottom: 0.5rem;
    
    ${props => props.isLight && tw`text-neutral-100`};

    &::after {
        content: '*';
        color: #ff5f58;
        margin-left: 0.25rem;
    }
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
