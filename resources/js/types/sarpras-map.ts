export type SarprasMarker = {
    id: number;
    name: string;
    province_id?: number | null;
    city_id?: number | null;
    district_id?: number | null;
    village_id?: number | null;
    latitude: number;
    longitude: number;
    province?: string | null;
    city?: string | null;
    district?: string | null;
    village?: string | null;
    sarpras_count: number;
    retail_ready?: boolean;
    mandatory_sarpras_count?: number;
    installed_mandatory_sarpras_count?: number;
    arrived_mandatory_sarpras_count?: number;
    status_counts?: {
        terpasang?: number;
        tiba?: number;
        pengiriman?: number;
        transit?: number;
        tanpa_status?: number;
        [key: string]: number | undefined;
    };
    delivery_percentage?: string | null;
    installed_percentage?: string | null;
    core_percentage?: string | null;
    sarprases:
        | { name: string; status?: string | null; is_mandatory?: boolean }[]
        | Record<string, { name: string; status?: string | null; is_mandatory?: boolean }>;
};

export type Pagination<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};
