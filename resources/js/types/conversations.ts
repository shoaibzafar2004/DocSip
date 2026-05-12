export interface Conversation {
    id: number;
    title: string | null;
    createdAt: string;
}

export interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    createdAt: string;
}
