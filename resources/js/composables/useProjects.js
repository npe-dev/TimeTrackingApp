import { ref } from 'vue';
import { useApi } from './useApi';

const projects = ref([]);

export function useProjects() {
    const api = useApi();

    async function loadProjects() {
        projects.value = await api.get('/projects');
    }

    async function createProject(data) {
        const project = await api.post('/projects', data);
        projects.value.push(project);
        return project;
    }

    async function updateProject(id, data) {
        const project = await api.put(`/projects/${id}`, data);
        const idx = projects.value.findIndex(p => p.id === id);
        if (idx >= 0) projects.value[idx] = project;
        return project;
    }

    async function deleteProject(id) {
        await api.del(`/projects/${id}`);
        projects.value = projects.value.filter(p => p.id !== id);
    }

    function getProjectById(id) {
        return projects.value.find(p => p.id === id);
    }

    return { projects, loadProjects, createProject, updateProject, deleteProject, getProjectById };
}
