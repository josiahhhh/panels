import React, { useEffect, useRef, useState } from 'react';
import tw, { theme } from 'twin.macro';
import ServerMetric from '@/components/iceline/server/ServerMetric';
import { ServerContext } from '@/state/server';
import { bytesToString, mbToBytes } from '@/lib/formatters';
// import Chart, { ChartConfiguration } from 'chart.js';
// import merge from 'deepmerge';
import getPlayerCount, { PlayerCount } from '@/api/server/iceline/players/getPlayerCount';
import { Line } from 'react-chartjs-2';
import { useChart, useChartTickLabel } from '@/components/iceline/server/console/chart';
import { hexToRgba } from '@/lib/helpers';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { SocketEvent } from '@/components/server/events';

/* interface Stats {
    memory: number;
    cpu: number;
    disk: number;
} */

/* const chartDefaults = (ticks?: Chart.TickOptions | undefined, datasetOptions?: Chart.ChartData | undefined): ChartConfiguration => ({
    type: 'line',
    options: {
        responsive: true,
        maintainAspectRatio: false,
        aspectRatio: 3,
        /* legend: {
            display: false,
        }, */
/* tooltips: {
            enabled: false,
        }, */
/* animation: {
            duration: 0.25,
        },
        elements: {
            point: {
                radius: 0,
            },
            line: {
                tension: 0.6,
                backgroundColor: 'rgba(15, 178, 184, 0.45)',
                borderColor: '#32D0D9',
            },
        },
        scales: {
            x: {
                ticks: {
                    display: false,
                },
                grid: {
                    display: false,
                },
            },
            y: {
                grid: {
                    drawTicks: false,
                    color: '#1e1f2d',
                    // zeroLineColor: 'transparent',
                    // zeroLineWidth: 3,
                    drawBorder: false,
                },
                ticks: merge(ticks || {}, {
                    fontSize: 10,
                    fontFamily: '"IBM Plex Mono", monospace',
                    fontColor: '#434556',
                    min: 0,
                    beginAtZero: true,
                    maxTicksLimit: 3,
                }),
            },
        },
    },
    data: {
        labels: Array(20).fill(''),
        datasets: [
            merge(datasetOptions || {}, {
                fill: false,
                borderWidth: 2,
                data: Array(20).fill(0),
            }),
        ],
    },
});  */

export default () => {
    // const [stats, setStats] = useState<Stats>({ memory: 0, cpu: 0, disk: 0 });
    const status = ServerContext.useStoreState((state) => state.status.value);
    const limits = ServerContext.useStoreState((state) => state.server.data!.limits);
    const previous = useRef<Record<'tx' | 'rx', number>>({ tx: -1, rx: -1 });

    const cpu = useChartTickLabel('CPU', limits.cpu || 100, '%');
    const memory = useChartTickLabel('Memory', limits.memory, 'MB', {
        tickFormatter: (value, label) => {
            return `${(value as number).toFixed(2)}${label}`;
        },
    });
    const network = useChart('Network', {
        sets: 2,
        options: {
            scales: {
                y: {
                    ticks: {
                        callback(value) {
                            return bytesToString(typeof value === 'string' ? parseInt(value, 10) : value);
                        },
                    },
                },
            },
        },
        callback(opts, index) {
            return {
                ...opts,
                label: !index ? 'Network In' : 'Network Out',
                borderColor: !index ? theme('colors.cyan.400') : theme('colors.yellow.400'),
                backgroundColor: hexToRgba(!index ? theme('colors.cyan.700') : theme('colors.yellow.700'), 0.5),
            };
        },
    });
    // TODO: set actual player max
    const players = useChartTickLabel('Players', 100, 'Players');
    const disk = useChartTickLabel('Disk', limits.disk / 1024, 'GB');

    // const playersInterval = useRef<number>(null);
    const [_, setPlayerCount] = useState<PlayerCount | null>(null);

    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);
    const connected = ServerContext.useStoreState((state) => state.socket.connected);
    const instance = ServerContext.useStoreState((state) => state.socket.instance);

    /* const updateChartGradient = (chart: Chart) => {
        if (!chart || !chart.data || !chart.data.datasets || chart!.data!.datasets!.length <= 0) {
            return;
        }

        if (chart.chartArea && chart.chartArea.left) {
            const ctx = chart.canvas!.getContext('2d')!;
            const lineGradient = ctx.createLinearGradient(chart.chartArea.left, 0, chart.width!, 0);
            lineGradient.addColorStop(0, 'rgba(39, 179, 210, 0.0)');
            lineGradient.addColorStop(0.2, 'rgba(39, 179, 210, 1.0)');
            lineGradient.addColorStop(0.8, 'rgba(39, 179, 210, 1.0)');
            lineGradient.addColorStop(1, 'rgba(39, 179, 210, 0.0)');
            chart.data.datasets[0].borderColor = lineGradient;
        }

        chart.update({
            lazy: true,
        });

        chart.resize();
    }; */

    useEffect(() => {
        if (status === 'offline') {
            cpu.clear();
            memory.clear();
            network.clear();
        }
    }, [status]);

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        let values: any = {};
        try {
            values = JSON.parse(data);
        } catch (e) {
            return;
        }

        cpu.push(values.cpu_absolute); // .toFixed(2)
        memory.push(Math.floor(values.memory_bytes / 1024 / 1024));
        network.push([
            previous.current.tx < 0 ? 0 : Math.max(0, values.network.tx_bytes - previous.current.tx),
            previous.current.rx < 0 ? 0 : Math.max(0, values.network.rx_bytes - previous.current.rx),
        ]);

        previous.current = { tx: values.network.tx_bytes, rx: values.network.rx_bytes };

        disk.push(values.disk_bytes / 1024 / 1024 / 1024); // push as gb
    });

    const getPlayersCount = (uid: string) =>
        getPlayerCount(uid)
            .then((data) => {
                if (data?.current) {
                    players.push(data.current);
                }
                setPlayerCount(data);

                if (data?.max && players.props.options.scales && players.props.options.scales.y) {
                    players.props.options.scales.y.max = data?.max;
                }

                /* console.log(data);
                console.log(playersChart);
                console.log(data?.max);
                if (playersChart && data?.max !== undefined) {
                    // @ts-ignore
                    // playersChart.options.scales.yAxes[0].ticks.max = data.max;
                    // @ts-ignore
                    playersChart.options.scales.yAxes[0].ticks.suggestedMax = data.max;
                    console.log(playersChart.options.scales);
                    playersChart.update();

                }*/

                /* if (playersChart && data?.current !== undefined) {
                updateChartGradient(playersChart, data?.current);
            } */
            })
            .catch((error) => console.error(error));

    useEffect(() => {
        if (!connected || !instance || !uuid) {
            return;
        }

        const interval = setInterval(() => getPlayersCount(uuid), 20000);

        getPlayersCount(uuid);

        return () => clearInterval(interval);
    }, [instance, connected, uuid]);

    return (
        <div css={tw`grid grid-cols-2 xl:grid-cols-4 gap-x-8 gap-y-6 w-full py-3 px-2`}>
            {/* <ServerMetric
                title={'Players'}
                metric={
                    status !== 'offline'
                        ? playerCount
                            ? `${playerCount?.current || '0'}/${playerCount?.max || '0'}`
                            : 'Fetching'
                        : 'Offline'
                }
            >
                {status !== 'offline' && <Line {...players.props} />}
            </ServerMetric>  */}
            <ServerMetric
                title={'CPU'}
                limit={limits.cpu ? `${limits.cpu}%` : 'Unlimited'}
                metric={
                    status !== 'offline'
                        ? `${cpu.props.data.datasets[0].data.length > 0 ? cpu.props.data.datasets[0].data[cpu.props.data.datasets[0].data.length - 1] : '? '}%`
                        : 'Offline'
                }
            >
                {status !== 'offline' && <Line {...cpu.props} />}
            </ServerMetric>
            <ServerMetric
                title={'RAM'}
                limit={`${limits.memory ? bytesToString(mbToBytes(limits.memory)) : 'Unlimited'}`}
                metric={
                    status !== 'offline'
                        ? `${
                              memory.props.data.datasets[0].data.length > 0
                                  ? bytesToString(((memory.props.data.datasets[0].data[memory.props.data.datasets[0].data.length - 1] as number | null) || 0) * 1024 * 1024)
                                  : '? MB'
                          }`
                        : 'Offline'
                }
            >
                {status !== 'offline' && <Line {...memory.props} />}
            </ServerMetric>
            <ServerMetric
                title={'Disk'}
                limit={`${limits.disk ? bytesToString(mbToBytes(limits.disk)) : 'Unlimited'}`}
                metric={`${
                    disk.props.data.datasets[0].data.length > 0
                        ? bytesToString(((disk.props.data.datasets[0].data[disk.props.data.datasets[0].data.length - 1] as number | null) || 0) * 1024 * 1024 * 1024)
                        : '? MB'
                }`}
            >
                {status !== 'offline' && <Line {...disk.props} />}
            </ServerMetric>
            <ServerMetric
                title={'Network'}
                metric={
                    status !== 'offline'
                        ? `${
                              network.props.data.datasets[0] && network.props.data.datasets[0].data.length > 0
                                  ? network.props.data.datasets[0].data[network.props.data.datasets[0].data.length - 1]
                                  : '? '
                          } up` +
                          '\n' +
                          `${
                              network.props.data.datasets[1] && network.props.data.datasets[1].data.length > 0
                                  ? network.props.data.datasets[1].data[network.props.data.datasets[1].data.length - 1]
                                  : '? '
                          } down`
                        : 'Offline'
                }
            >
                {status !== 'offline' && <Line {...network.props} />}
            </ServerMetric>
        </div>
    );
};
