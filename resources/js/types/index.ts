export * from './auth';
export * from './navigation';
export * from './ui';

export interface GymImage {
    id: number;
    gym_id: number;
    img_url: string;
}

export interface UserAdmin {
    id: number;
    name: string;
}

export interface PersonalTrainer {
    id: number;
    name: string;
}

export interface Gym {
    id: number;
    name: string;
    address: string;
    address_coordinate: string | null;
    description: string | null;
    start_access: string;
    gym_images?: GymImage[];
    admins?: UserAdmin[];
    personal_trainers?: PersonalTrainer[];
}

export interface PaginatedData<T> {
    data: T[];
    links: any[];
    current_page: number;
}