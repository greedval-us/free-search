export {};

declare global {
    interface Window {
        __OSINT_FRONTEND_CONFIG__?: {
            apiRetry?: {
                default?: {
                    attempts?: number;
                    base_delay_ms?: number;
                    max_delay_ms?: number;
                    retry_on_network_error?: boolean;
                    retry_on_statuses?: number[];
                };
                endpoint_rules?: Array<{
                    path: string;
                    methods?: string[];
                    policy?: {
                        attempts?: number;
                        base_delay_ms?: number;
                        max_delay_ms?: number;
                        retry_on_statuses?: number[];
                        retry_on_network_error?: boolean;
                    };
                }>;
            };
        };
    }
}
