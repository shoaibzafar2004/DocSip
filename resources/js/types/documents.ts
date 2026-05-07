export interface Document {
    id: number;
    name: string;
    status: 'uploaded' | 'processing' | 'ready';
    createdAt: string;
}
