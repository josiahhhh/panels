import React, { forwardRef, useEffect, useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { FieldProps, Form, Formik, FormikHelpers, Field as FormikField, useFormikContext } from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateSchedule from '@/api/server/schedules/createOrUpdateSchedule';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import tw, { theme } from 'twin.macro';
import Button from '@/components/elements/Button';
import Label from '@/components/elements/Label';
import Input from '@/components/elements/Input';
import styled from 'styled-components/macro';
import Select from 'react-select';

type Props = {
    schedule?: Schedule;
} & RequiredModalProps;

interface Values {
    name: string;
    dayOfWeek: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    month: string;
    onlyWhenOnline: boolean;
    enabled: boolean;
}

interface CronFieldProps {
    label: string;
    description?: string;
    name: string;
    disabled?: boolean;
}

type OptionType = {
    value: string;
    label: string;
    values: {
        minute: string;
        hour: string;
        dayOfMonth: string;
        dayOfWeek: string;
        month: string;
    };
};

const quickSelectOptions: OptionType[] = [
    {
        label: 'Every 5 Minutes',
        value: '*/5 * * *',
        values: {
            minute: '*/5',
            hour: '*',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 10 Minutes',
        value: '*/10 * * *',
        values: {
            minute: '*/10',
            hour: '*',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 15 Minutes',
        value: '*/15 * * *',
        values: {
            minute: '*/15',
            hour: '*',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 30 Minutes',
        value: '*/30 * * *',
        values: {
            minute: '*/30',
            hour: '*',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every hour',
        value: '0 * * *',
        values: {
            minute: '0',
            hour: '*',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 2 hours',
        value: '0 */2 * *',
        values: {
            minute: '0',
            hour: '*/2',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 5 hours',
        value: '0 */5 * *',
        values: {
            minute: '0',
            hour: '*/5',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 8 hours',
        value: '0 */8 * *',
        values: {
            minute: '0',
            hour: '*/8',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Every 12 hours',
        value: '0 */12 * *',
        values: {
            minute: '0',
            hour: '*/12',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Once a day',
        value: '0 0 * *',
        values: {
            minute: '0',
            hour: '0',
            dayOfMonth: '*',
            dayOfWeek: '*',
            month: '*',
        },
    },
    {
        label: 'Once a week',
        value: '0 0 * 0',
        values: {
            minute: '0',
            hour: '0',
            dayOfMonth: '*',
            dayOfWeek: '0',
            month: '*',
        },
    },
];

interface QuickSelectFieldProps {
    disabled?: boolean;
}

const QuickSelectField = forwardRef<HTMLInputElement, QuickSelectFieldProps>(({ disabled }, ref) => {
    return (
        <FormikField innerRef={ref} name={name}>
            {({ form: { values, setFieldValue, setFieldTouched } }: FieldProps) => (
                <>
                    <Label>Schedule Frequency</Label>
                    <Select
                        options={quickSelectOptions}
                        blurInputOnSelect
                        hideSelectedOptions
                        defaultValue={quickSelectOptions[0]}
                        isDisabled={disabled}
                        value={quickSelectOptions.find((e) => {
                            return (
                                e.values.minute === values.minute &&
                                e.values.hour === values.hour &&
                                e.values.dayOfMonth === values.dayOfMonth &&
                                e.values.dayOfWeek === values.dayOfWeek &&
                                e.values.month === values.month
                            );
                        })}
                        onChange={(v) => {
                            if (v) {
                                setFieldValue('minute', v.values.minute);
                                setFieldValue('hour', v.values.hour);
                                setFieldValue('dayOfMonth', v.values.dayOfMonth);
                                setFieldValue('dayOfWeek', v.values.dayOfWeek);
                                setFieldValue('month', v.values.month);
                                setFieldTouched('minute', true, true);
                                setFieldTouched('hour', true, true);
                                setFieldTouched('dayOfMonth', true, true);
                                setFieldTouched('dayOfWeek', true, true);
                                setFieldTouched('month', true, true);
                            }
                        }}
                        theme={(theTheme) => ({
                            ...theTheme,
                            colors: {
                                ...theTheme.colors,
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
                            },
                        })}
                    />
                    <p css={tw`mt-1 text-xs text-neutral-200`}>Select a schedule interval from our selection of common schedule intervalsr.</p>
                </>
            )}
        </FormikField>
    );
});

const CronField = forwardRef<HTMLInputElement, CronFieldProps>(({ name, label, description, disabled, ...props }, ref) => {
    return (
        <FormikField innerRef={ref} name={name}>
            {({ field, form: { errors, touched } }: FieldProps) => (
                <>
                    <Label>{label}</Label>
                    <Input {...field} {...props} hasError={!!(touched[field.name] && errors[field.name])} disabled={disabled} />
                    {touched[name] && errors[field.name] ? (
                        <p className={'input-help error'}>{(errors[field.name] as string).charAt(0).toUpperCase() + (errors[field.name] as string).slice(1)}</p>
                    ) : description ? (
                        <p className={'input-help'}>{description}</p>
                    ) : null}
                </>
            )}
        </FormikField>
    );
});

export const CronFieldCheckbox = styled(Input)`
    && {
        ${tw`border-neutral-500 bg-transparent`};
        &:not(:checked) {
            ${tw`hover:border-neutral-300`};
        }
    }
`;

const EditScheduleModal = ({ schedule, ...props }: Omit<Props, 'onScheduleUpdated'>) => {
    const { isSubmitting } = useFormikContext();
    const [useCustomExpression, setUseCustomExpression] = useState(false);

    useEffect(() => {
        if (!schedule) {
            return;
        }

        const foundOption = quickSelectOptions.find((e) => {
            return (
                e.values.minute === schedule.cron.minute &&
                e.values.hour === schedule.cron.hour &&
                e.values.dayOfMonth === schedule.cron.dayOfMonth &&
                e.values.dayOfWeek === schedule.cron.dayOfWeek &&
                e.values.month === schedule.cron.month
            );
        });

        setUseCustomExpression(!foundOption);
    }, [schedule]);

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <h3 css={tw`text-2xl mb-6`}>{schedule ? 'Edit schedule' : 'Create new schedule'}</h3>
            <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`} />
            <Form>
                <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6`}>
                    <div>
                        <Field name={'name'} label={'Schedule name'} description={'A human readable identifer for this schedule.'} />
                    </div>
                    <div>
                        <QuickSelectField disabled={useCustomExpression} />
                    </div>
                </div>
                <h1 css={tw`text-xl mt-6 flex flex-row items-center justify-between`}>
                    <span>Custom Expression</span>
                    <div css={tw`flex flex-row items-start`}>
                        <CronFieldCheckbox
                            id={'custom-expression'}
                            name={'custom-expression'}
                            checked={useCustomExpression}
                            type={'checkbox'}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                if (e.currentTarget.checked) {
                                    setUseCustomExpression(true);
                                } else {
                                    setUseCustomExpression(false);
                                }
                            }}
                            css={tw`mr-1`}
                        />
                        <Label htmlFor={'custom-expression'}>Use custom expression</Label>
                    </div>
                </h1>
                <div
                    style={{
                        opacity: `${useCustomExpression ? '1' : '0.35'}`,
                    }}
                >
                    <div css={tw`grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4`}>
                        <div>
                            <CronField name={'minute'} label={'Minute'} disabled={!useCustomExpression} />
                        </div>
                        <div>
                            <CronField name={'hour'} label={'Hour'} disabled={!useCustomExpression} />
                        </div>
                        <div>
                            <CronField name={'dayOfMonth'} label={'Day of month'} disabled={!useCustomExpression} />
                        </div>
                        <div>
                            <CronField name={'dayOfWeek'} label={'Day of week'} disabled={!useCustomExpression} />
                        </div>
                        <div>
                            <CronField name={'month'} label={'Month'} disabled={!useCustomExpression} />
                        </div>
                    </div>
                    <p css={tw`mt-1 text-xs text-neutral-200`}>This section allows you to optionally specify a custom cron expression for the schedule interval.</p>
                </div>
                <div css={tw`mt-6 bg-icelinebox-700 border border-icelinebox-500 hover:border-icelinebrandcolour-500 shadow-inner p-4 rounded`}>
                    <FormikSwitch name={'onlyWhenOnline'} description={'If enabled, this schedule will only run when the server is online.'} label={'Only When Online'} />
                </div>
                <div css={tw`mt-6 bg-icelinebox-700 border border-icelinebox-500 hover:border-icelinebrandcolour-500 shadow-inner p-4 rounded`}>
                    <FormikSwitch name={'enabled'} description={"If disabled, this schedule and it's associated tasks will not run."} label={'Enabled'} />
                </div>
                <div css={tw`mt-6 flex flex-row items-center justify-between`}>
                    <span css={tw`text-base text-neutral-500 font-medium`}>Schedule timezone: {(window as any).panelTimezone}</span>
                    <Button css={tw`w-full sm:w-auto`} type={'submit'} disabled={isSubmitting}>
                        {schedule ? 'Save changes' : 'Create schedule'}
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default ({ schedule, visible, ...props }: Props) => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addError, clearFlashes } = useFlash();
    const [modalVisible, setModalVisible] = useState(visible);

    const appendSchedule = ServerContext.useStoreActions((actions) => actions.schedules.appendSchedule);

    useEffect(() => {
        setModalVisible(visible);
        clearFlashes('schedule:edit');
    }, [visible]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:edit');
        createOrUpdateSchedule(uuid, {
            id: schedule?.id,
            name: values.name,
            cron: {
                minute: values.minute,
                hour: values.hour,
                dayOfWeek: values.dayOfWeek,
                dayOfMonth: values.dayOfMonth,
                month: values.month,
            },
            onlyWhenOnline: values.onlyWhenOnline,
            isActive: values.enabled,
        })
            .then((schedule) => {
                setSubmitting(false);
                appendSchedule(schedule);
                setModalVisible(false);
            })
            .catch((error) => {
                console.error(error);

                setSubmitting(false);
                addError({ key: 'schedule:edit', message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={
                {
                    name: schedule?.name || '',
                    dayOfWeek: schedule?.cron.dayOfWeek || '*',
                    dayOfMonth: schedule?.cron.dayOfMonth || '*',
                    hour: schedule?.cron.hour || '*',
                    minute: schedule?.cron.minute || '*',
                    month: schedule?.cron.month || '*',
                    onlyWhenOnline: schedule ? schedule.onlyWhenOnline : false,
                    enabled: schedule ? schedule.isActive : true,
                } as Values
            }
            validationSchema={null}
        >
            <EditScheduleModal visible={modalVisible} schedule={schedule} {...props} />
        </Formik>
    );
};
