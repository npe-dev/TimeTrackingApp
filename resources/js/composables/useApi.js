import axios from 'axios';

export function useApi() {
    async function get(url, params = {}) {
        const { data } = await axios.get(url, { params });
        return data;
    }

    async function post(url, body = {}) {
        const { data } = await axios.post(url, body);
        return data;
    }

    async function put(url, body = {}) {
        const { data } = await axios.put(url, body);
        return data;
    }

    async function patch(url, body = {}) {
        const { data } = await axios.patch(url, body);
        return data;
    }

    async function del(url) {
        const { data } = await axios.delete(url);
        return data;
    }

    return { get, post, put, patch, del };
}
