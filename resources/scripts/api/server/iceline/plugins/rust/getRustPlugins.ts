import http from '@/api/http';

export function toRustPlugin(manifest: any, extra?: any): RustPlugin {
    if (!manifest) {
        // eslint-disable-next-line no-prototype-builtins
        if (extra.hasOwnProperty('filename')) {
            const installInfo = extra as {
                filename: string;
                name: string;
                version: string;
            };

            return {
                author: '',
                authorIconURL: '',
                authorID: '',
                categoryTags: '',
                createdAt: '',
                createdAtAtom: '',
                description: 'Plugin locally installed by a server administrator.',
                donateURL: '',
                downloadURL: '',
                downloads: 0,
                downloadsShortened: '',
                gamesDetail: [],
                iconURL: '',
                jsonURL: '',
                latestReleaseAt: '',
                latestReleaseAtAtom: '',
                latestReleaseVersion: '',
                latestReleaseVersionChecksum: '',
                latestReleaseVersionFormatted: '',
                name: installInfo.name,
                publishedAt: '',
                slug: installInfo.filename,
                statusDetail: {
                    icon: '',
                    text: '',
                    message: '',
                    value: 0,
                    class: '',
                },
                tagsAll: ['local'],
                title: installInfo.name,
                updatedAt: '',
                updatedAtAtom: '',
                url: '',
                watchers: 0,
                watchersShortened: '',
            };
        }
    }

    return {
        author: manifest.author,
        authorIconURL: manifest.author_icon_url,
        authorID: manifest.author_id,
        categoryTags: manifest.category_tags,
        createdAt: manifest.created_at,
        createdAtAtom: manifest.created_at_atom,
        description: manifest.description,
        donateURL: manifest.donate_url,
        downloadURL: manifest.download_url,
        downloads: manifest.downloads,
        downloadsShortened: manifest.downloads_shortened,
        gamesDetail: manifest.games_detail.map((d: any) => ({
            iconUrl: d.icon_url,
            name: d.name,
        })),
        iconURL: manifest.icon_url,
        jsonURL: manifest.json_url,
        latestReleaseAt: manifest.latest_release_at,
        latestReleaseAtAtom: manifest.latest_release_at_atom,
        latestReleaseVersion: manifest.latest_release_version,
        latestReleaseVersionChecksum: manifest.latest_release_version_checksum,
        latestReleaseVersionFormatted: manifest.latest_release_version_formatted,
        name: manifest.name,
        publishedAt: manifest.published_at,
        slug: manifest.slug,
        statusDetail: {
            icon: manifest.status_detail.icon,
            text: manifest.status_detail.text,
            message: manifest.status_detail.message,
            value: manifest.status_detail.value,
            class: manifest.status_detail.class,
        },
        tagsAll: manifest.tags_all.split(','), // comma seperated string
        title: manifest.title,
        updatedAt: manifest.updated_at,
        updatedAtAtom: manifest.updated_at_atom,
        url: manifest.url,
        watchers: manifest.watchers,
        watchersShortened: manifest.watchers_shortened,
    };
}

export interface RustPlugin {
    author: string;
    authorIconURL: string;
    authorID: string;
    categoryTags: string;
    createdAt: string;
    createdAtAtom: string;
    description: string;
    donateURL: string;
    downloadURL: string;
    downloads: number;
    downloadsShortened: string;
    gamesDetail: {
        iconURL: string;
        name: string;
    }[];
    iconURL: string;
    jsonURL: string;
    latestReleaseAt: string;
    latestReleaseAtAtom: string;
    latestReleaseVersion: string;
    latestReleaseVersionChecksum: string;
    latestReleaseVersionFormatted: string;
    name: string;
    publishedAt: string;
    slug: string;
    statusDetail: {
        icon: string;
        text: string;
        message: string;
        value: number;
        class: string;
    };
    tagsAll: string[];
    title: string;
    updatedAt: string;
    updatedAtAtom: string;
    url: string;
    watchers: number;
    watchersShortened: string;
}

interface Options {
    query?: string;
    page?: number;
}

export default async (uuid: string, options?: Options): Promise<RustPlugin[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/plugins/rust`, {
        params: {
            query: options?.query,
            page: options?.page,
        },
    });

    return data.data.map((p: any) => toRustPlugin(p)) || [];
};
