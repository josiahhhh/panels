import React, { useEffect, useState } from 'react';
import GreyRowBox from '@/components/elements/GreyRowBox';
import tw from 'twin.macro';
import Sockette from 'sockette';

interface Props {
    name: string;
    short: string;
    long: string;
    current?: boolean;
    selected?: boolean;
    onClick?: () => void;
    address?: string;
    onPing?: (ping: number) => void;
    showPing?: boolean;
}

export default ({ short, long, current, selected, onClick, address, onPing, showPing }: Props) => {
    const [ping, setPing] = useState(-1 as number);
    const samples = [] as number[];

    useEffect(() => {
        if (address && showPing) {
            const ws = new Sockette(`wss://${address}/ping`, {
                timeout: 5e3,
                maxAttempts: 10,
                onopen: (e) => {
                    console.log('Connected!', e);

                    for (let i = 0; i < 10; i++) {
                        setTimeout(
                            () => {
                                ws.send(new Date().getTime());
                            },
                            i < 3 ? 200 * i : 800 * i
                        );
                    }
                },
                onmessage: (e) => {
                    const ended = new Date().getTime();
                    const milliseconds = ended - parseInt(e.data);

                    samples.push(milliseconds);
                    if (samples.length >= 3) {
                        const sum = samples.reduce((a, b) => a + b, 0);
                        setPing(sum / samples.length || 0);

                        if (onPing) {
                            onPing(ping);
                        }
                    }

                    if (samples.length >= 10) {
                        ws.close();
                    }
                },
                onreconnect: (e) => console.log('Reconnecting...', e),
                onmaximum: (e) => console.log('Stop Attempting!', e),
                onclose: (e) => console.log('Closed!', e),
                onerror: (e) => console.log('Error:', e),
            });
        }
    }, [address]);

    return (
        <GreyRowBox css={tw`flex flex-row items-center mt-4 cursor-pointer`} onClick={onClick}>
            <span
                css={tw`rounded-full bg-neutral-700 mr-6 w-10 h-10`}
                style={{
                    backgroundImage: `url(/assets/iceline/locations/${short ? short.split('-')[0] : 'default'}.png)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                }}
            />
            <div css={tw`flex flex-col`}>
                <h1 css={tw`text-base font-normal text-neutral-100`}>{long || short}</h1>
                <h1 css={tw`text-xs font-normal text-neutral-400`}>
                    {showPing ? address ? ping <= 0 ? <span>Pinging...</span> : <span>{ping.toFixed(0)} ms</span> : <span>Unavailable</span> : <span>{short}</span>}
                </h1>
            </div>
            <span css={tw`flex-grow`} />
            {current && <div css={tw`rounded-lg p-2 py-1 bg-icelinebox-400 text-base`}>Current</div>}
            {selected && <div css={tw`rounded-lg p-2 py-1 bg-primary-500 text-base text-neutral-50`}>Selected</div>}
        </GreyRowBox>
    );
};
