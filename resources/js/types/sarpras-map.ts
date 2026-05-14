export type SarprasMarker = {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    province?: string | null;
    city?: string | null;
    district?: string | null;
    village?: string | null;
    sarpras_count: number;
    delivery_percentage?: string | null;
    installed_percentage?: string | null;
    core_percentage?: string | null;
    sarprases: { name: string; status?: string | null }[] | Record<string, { name: string; status?: string | null }>;
    detail_url: string;
};

export type Pagination<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};
