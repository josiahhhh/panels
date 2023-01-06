import React, { useEffect, useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import getStaffRequests from '@/api/staff/getStaffRequests';
import useFlash from '@/plugins/useFlash';
import tw, { theme } from 'twin.macro';
import useSWR from 'swr';
import Spinner from '@/components/elements/Spinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { Field as FormikField, Form, Formik, FormikHelpers, FieldProps } from 'formik';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Button from '@/components/elements/Button';
import makeStaffRequest from '@/api/staff/makeStaffRequest';
import { number, object, string } from 'yup';
import Field from '@/components/elements/Field';
import Label from '@/components/elements/Label';
// import Select from '@/components/elements/Select';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons';
import styled from 'styled-components/macro';
import { Link } from 'react-router-dom';
import DeleteStaffRequestButton from '@/components/dashboard/staff/DeleteStaffRequestButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import Select, { Props, Theme, ThemeConfig } from 'react-select';

const Code = styled.code`
    ${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}
`;

export interface StaffRequestResponse {
    servers: any[];
    requests: any[];
}

interface CreateValues {
    server: number;
    message: string;
}

const SelectTheme: ThemeConfig = (originalTheme: Theme): Theme => {
    return {
        ...originalTheme,
        colors: {
            ...originalTheme.colors,
            primary: theme`colors.icelinebrandcolour.500`.toString(),
            primary75: theme`colors.icelinebrandcolour.500`.toString(),
            primary50: theme`colors.icelinebrandcolour.500`.toString(),
            primary25: theme`colors.icelinebrandcolour.500`.toString(),
            neutral0: theme`colors.icelinebox.600`.toString(), // control background
            neutral5: theme`colors.icelinebox.600`.toString(),
            neutral10: theme`colors.icelinebox.600`.toString(),
            neutral20: theme`colors.icelinebox.400`.toString(), // control border, indicators/color
            neutral30: theme`colors.icelinebox.200`.toString(), // control border focused/hover
            neutral40: theme`colors.icelinebox.300`.toString(), // indicators/color:hover
            neutral50: theme`colors.icelinebox.400`.toString(),
            neutral60: theme`colors.icelinebox.300`.toString(),
            neutral70: theme`colors.icelinebox.200`.toString(),
            neutral80: theme`colors.icelinebox.100`.toString(),
            neutral90: theme`colors.icelinebox.50`.toString(),
            danger: theme`colors.red.500`.toString(),
            dangerLight: theme`colors.red.200`.toString(),
        },
    };
};

declare type SelectOption = {
    value: string;
    label: string;
};

const SelectField = ({ options, field, form }: Props & FieldProps) => {
    const onChange = (option: unknown) => {
        if (option) {
            form.setFieldValue(field.name, (option as SelectOption).value);
        }
    };

    return (
        <Select
            options={options}
            name={field.name}
            value={options ? options.find((option: unknown) => (option as SelectOption).value === field.value) : ''}
            onChange={onChange}
            onBlur={field.onBlur}
            theme={SelectTheme}
        />
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<StaffRequestResponse>(['/staff'], () => getStaffRequests());

    const [isSubmit, setSubmit] = useState(false);

    useEffect(() => {
        if (!error) {
            clearFlashes('staff');
        } else {
            clearAndAddHttpError({ key: 'staff', error });
        }
    });

    const submit = ({ server, message }: CreateValues, { setSubmitting }: FormikHelpers<CreateValues>) => {
        clearFlashes('staff');
        clearFlashes('staff:create');
        setSubmitting(false);
        setSubmit(true);

        console.log(server);

        makeStaffRequest(server, message)
            .then(() => {
                mutate();
                setSubmit(false);
            })
            .catch((error) => {
                setSubmitting(false);
                setSubmit(false);
                clearAndAddHttpError({ key: 'staff:create', error });
            });
    };

    return (
        <PageContentBlock title={'Staff Request'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'staff'} css={tw`mb-4`} />
            </div>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'staff:create'} css={tw`mb-4`} />
            </div>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <div css={tw`w-full lg:w-8/12 mt-4 lg:mt-0`}>
                        <TitledGreyBox title={'Request Access'}>
                            <div css={tw`px-1 py-2`}>
                                <Formik
                                    onSubmit={submit}
                                    initialValues={{ server: data.servers[0]?.id, message: '' }}
                                    validationSchema={object().shape({
                                        server: number().required(),
                                        message: string().required(),
                                    })}
                                >
                                    <Form>
                                        <div css={tw`flex flex-wrap`}>
                                            <div css={tw`mb-6 w-full lg:w-1/2`}>
                                                <Label>Server</Label>
                                                <FormikFieldWrapper name={'Server'}>
                                                    <FormikField
                                                        component={SelectField}
                                                        options={data.servers.map((item) => ({
                                                            value: item.id,
                                                            label: item.name,
                                                        }))}
                                                        name={'server'}
                                                    />
                                                </FormikFieldWrapper>
                                            </div>
                                            <div css={tw`mb-6 w-full lg:w-1/2 lg:pl-4`}>
                                                <Field name={'message'} label={'Message'} />
                                            </div>
                                        </div>
                                        <div css={tw`flex justify-end`}>
                                            <Button type={'submit'} disabled={isSubmit}>
                                                Request Access
                                            </Button>
                                        </div>
                                    </Form>
                                </Formik>
                            </div>
                        </TitledGreyBox>

                        {data.requests.length < 1 ? (
                            <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>There are no requests.</p>
                        ) : (
                            data.requests.map((item, key) => (
                                <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                                    <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                                        <div css={tw`flex items-center w-full md:w-auto`}>
                                            <div css={tw`pr-2 text-neutral-400`}>
                                                <FontAwesomeIcon icon={faServer} />
                                            </div>
                                            <div css={tw`flex-1 md:w-64`}>
                                                <Code as={Link} to={`/server/${item.server.uuidShort}`}>
                                                    {item.server.name}
                                                </Code>
                                                <Label>Server Name</Label>
                                            </div>
                                            <div css={tw`flex-1 md:w-32`}>
                                                <Code>{item.status}</Code>
                                                <Label>Status</Label>
                                            </div>
                                            <div css={tw`flex-1 md:w-48`}>
                                                <Code>{item.updated_at}</Code>
                                                <Label>Updated</Label>
                                            </div>
                                        </div>
                                        <div css={tw`w-full md:flex-none md:w-32 md:text-center mt-4 md:mt-0 text-right`}>
                                            <DeleteStaffRequestButton id={item.id} onDeleted={() => mutate()} />
                                        </div>
                                    </GreyRowBox>
                                </GreyRowBox>
                            ))
                        )}
                    </div>
                    <div css={tw`w-full lg:w-4/12 lg:pl-4`}>
                        <TitledGreyBox title={'Request Help'}>
                            <div css={tw`px-1 py-2`}>You can send requests to server owners that you want to access the server. The owner can accept or deny it.</div>
                        </TitledGreyBox>
                    </div>
                </>
            )}
        </PageContentBlock>
    );
};
