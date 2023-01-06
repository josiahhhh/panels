import React, { useEffect } from 'react';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import useSWR from 'swr';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Label from '@/components/elements/Label';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser } from '@fortawesome/free-solid-svg-icons';
import styled from 'styled-components/macro';
import getStaffRequests from '@/api/server/staff/getStaffRequests';
import AcceptStaffRequestButton from '@/components/server/staff/AcceptStaffRequestButton';
import DenyStaffRequestButton from '@/components/server/staff/DenyStaffRequestButton';

const Code = styled.code`
    ${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}
`;

export interface StaffResponse {
    requests: any[];
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data, error, mutate } = useSWR<StaffResponse>([uuid, '/staff'], (key) => getStaffRequests(key));

    useEffect(() => {
        if (!error) {
            clearFlashes('server:staff');
        } else {
            clearAndAddHttpError({ key: 'server:staff', error });
        }
    }, [error]);

    return (
        <ServerContentBlock title={'Staff Requests'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'server:staff'} css={tw`mb-4`} />
            </div>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <div css={tw`w-full lg:w-8/12 mt-4 lg:mt-0`}>
                        {data.requests.length < 1 ? (
                            <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>There are no access requests for this server.</p>
                        ) : (
                            data.requests.map((item, key) => (
                                <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                                    <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                                        <div css={tw`flex items-center w-full md:w-auto`}>
                                            <div css={tw`pr-4 text-neutral-400`}>
                                                <FontAwesomeIcon icon={faUser} />
                                            </div>
                                            <div css={tw`flex-1 w-64`}>
                                                <Code>
                                                    {item.user.name_last} {item.user.name_first}
                                                </Code>
                                                <Label>Staff</Label>
                                            </div>
                                            <div css={tw`flex-1 w-64 ml-4`}>
                                                <Code>{item.message}</Code>
                                                <Label>Message</Label>
                                            </div>
                                            <div css={tw`flex-1 w-16`}>
                                                <Code>{item.status}</Code>
                                                <Label>Status</Label>
                                            </div>
                                        </div>
                                        <div css={tw`w-full md:flex-none md:w-32 md:text-center mt-4 md:mt-0 text-right`}>
                                            {item.status_code !== 2 && <AcceptStaffRequestButton staffId={item.id} onDeleted={() => mutate()}></AcceptStaffRequestButton>}
                                            {item.status_code !== 3 && <DenyStaffRequestButton staffId={item.id} onDeleted={() => mutate()}></DenyStaffRequestButton>}
                                        </div>
                                    </GreyRowBox>
                                </GreyRowBox>
                            ))
                        )}
                    </div>
                    <div css={tw`w-full lg:w-4/12 lg:pl-4`}>
                        <TitledGreyBox title={'Staff Requests Help'}>
                            <div css={tw`px-1 py-2`}>Staffs can request an access to your server. If you accept it, staff can manage it. But you can deny it anytime.</div>
                        </TitledGreyBox>
                    </div>
                </>
            )}
        </ServerContentBlock>
    );
};
