import http from '@/api/http';

export enum WipeType {
    blueprint = 'blueprint',
    map = 'map',
    fall = 'fall',
}

export default (uuid: string, wipeType: WipeType): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/games/rust/wipe`, {
            wipe: wipeType,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
