import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { computed, unref } from 'vue';

const defaultQueryOptions = {
    staleTime: 1000 * 60 * 5,
    gcTime: 1000 * 60 * 30,
    refetchOnWindowFocus: true,
    retry: 1,
};

export function useApiQuery(key, url, options = {}) {
    const queryKey = computed(() => {
        const keyValue = unref(key);
        return Array.isArray(keyValue) ? keyValue : [keyValue];
    });

    const queryFn = async () => {
        const urlValue = unref(url);
        const response = await axios.get(urlValue);
        return response.data;
    };

    return useQuery({
        queryKey,
        queryFn,
        ...defaultQueryOptions,
        ...options,
    });
}

export function useApiMutation(options = {}) {
    const queryClient = useQueryClient();

    const {
        url,
        method = 'post',
        invalidateKeys = [],
        onSuccessCallback,
        onErrorCallback,
        optimistic = false,
        optimisticUpdater,
    } = options;

    return useMutation({
        mutationFn: async (data) => {
            const urlValue = unref(url);
            const response = await axios[method](urlValue, data);
            return response.data;
        },
        onMutate: async (newData) => {
            if (!optimistic || !optimisticUpdater) return;

            await queryClient.cancelQueries({ queryKey: invalidateKeys[0] });

            const previousData = queryClient.getQueryData(invalidateKeys[0]);

            queryClient.setQueryData(invalidateKeys[0], (old) =>
                optimisticUpdater(old, newData)
            );

            return { previousData };
        },
        onError: (error, variables, context) => {
            if (optimistic && context?.previousData) {
                queryClient.setQueryData(invalidateKeys[0], context.previousData);
            }
            onErrorCallback?.(error, variables);
        },
        onSuccess: (data, variables) => {
            invalidateKeys.forEach((key) => {
                queryClient.invalidateQueries({ queryKey: key });
            });
            onSuccessCallback?.(data, variables);
        },
        onSettled: () => {
            invalidateKeys.forEach((key) => {
                queryClient.invalidateQueries({ queryKey: key });
            });
        },
    });
}

export function usePaginatedQuery(key, urlBuilder, options = {}) {
    const {
        page = 1,
        perPage = 15,
        filters = {},
        ...queryOptions
    } = options;

    const queryKey = computed(() => {
        const keyValue = unref(key);
        const pageValue = unref(page);
        const filtersValue = unref(filters);
        return [keyValue, { page: pageValue, ...filtersValue }];
    });

    const queryFn = async ({ queryKey: qk }) => {
        const [, params] = qk;
        const url = urlBuilder(params);
        const response = await axios.get(url);
        return response.data;
    };

    return useQuery({
        queryKey,
        queryFn,
        ...defaultQueryOptions,
        placeholderData: (previousData) => previousData,
        ...queryOptions,
    });
}

export function useInfiniteApiQuery(key, urlBuilder, options = {}) {
    const queryClient = useQueryClient();

    const queryKey = computed(() => {
        const keyValue = unref(key);
        return Array.isArray(keyValue) ? keyValue : [keyValue];
    });

    return {
        queryKey,
        prefetch: (params) => {
            return queryClient.prefetchQuery({
                queryKey: [...queryKey.value, params],
                queryFn: async () => {
                    const url = urlBuilder(params);
                    const response = await axios.get(url);
                    return response.data;
                },
            });
        },
    };
}

export function prefetchQuery(queryClient, key, url) {
    return queryClient.prefetchQuery({
        queryKey: Array.isArray(key) ? key : [key],
        queryFn: async () => {
            const response = await axios.get(url);
            return response.data;
        },
        staleTime: defaultQueryOptions.staleTime,
    });
}

export function invalidateQueries(queryClient, keys) {
    const keysArray = Array.isArray(keys) ? keys : [keys];
    keysArray.forEach((key) => {
        queryClient.invalidateQueries({ queryKey: Array.isArray(key) ? key : [key] });
    });
}
