import React, { useEffect, useState } from 'react';
import { FieldProps } from 'formik';
import Label from '@/components/elements/Label';
import Input from '@/components/elements/Input';
import Select from '@/components/elements/Select';
import tw from 'twin.macro';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import { ServerContext } from '@/state/server';
import getServerDatabases from '@/api/server/databases/getServerDatabases';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';

export default (props: FieldProps<string>) => {
    const { addError, clearFlashes } = useFlash();

    const [name, setName] = useState('');
    const [database, setDatabase] = useState('');

    const [loading, setLoading] = useState(true);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const databases = useDeepMemoize(ServerContext.useStoreState((state) => state.databases.data));
    const setDatabases = ServerContext.useStoreActions((state) => state.databases.setDatabases);

    useEffect(() => {
        const newVal = JSON.stringify({
            name: name,
            database: database,
        });
        console.log(newVal);
        props.form.setFieldValue(props.field.name, newVal);
        props.form.setFieldTouched(props.field.name, true, true);
    }, [name, database]);

    useEffect(() => {
        if (props.field.value !== '') {
            const { name: newName, database: newDatabase } = JSON.parse(props.field.value);

            setName(newName);
            setDatabase(newDatabase);
        }
    }, [props.field.value]);

    useEffect(() => {
        clearFlashes('databases:get');
        setLoading(true);

        getServerDatabases(uuid)
            .then((databases) => {
                setDatabases(databases);

                if (databases.length > 0) {
                    setDatabase(databases[0].id);
                }
            })
            .catch((error) => {
                console.error(error);
                addError({ key: 'databases:get', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <div>
            <FlashMessageRender byKey={'databases:get'} css={tw`mb-4`} />
            <Label>Backup Name</Label>
            <Input value={name} onChange={(e) => setName(e.target.value)} />
            <p css={tw`mt-1 text-xs text-neutral-400`}>(Optional) Descriptive name for the database backup.</p>
            <Label css={tw`mt-4`}>Database</Label>
            {loading ? (
                <Spinner centered />
            ) : (
                <Select value={database} onChange={(e) => setDatabase(e.target.value)}>
                    {databases.map((db, index) => (
                        <option key={index} value={db.id}>
                            {db.name}
                        </option>
                    ))}
                </Select>
            )}
            <p css={tw`mt-1 text-xs text-neutral-400`}>Select the database for the backup.</p>
            {props.meta && props.meta.touched && Boolean(props.meta.error) && <p css={tw`mt-1 text-xs text-red-600`}>{props.meta.error}</p>}
        </div>
    );
};
