<template>
  <div class="min-h-screen bg-gray-100">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Project Reports</p>
            
            <div class="mt-8">
              <!-- Error Alert -->
              <div v-if="error" class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm text-red-700">
                      {{ error }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col space-y-4 sm:flex-row sm:justify-between sm:items-center mb-6">
                <div class="w-full sm:w-1/3">
                  <BaseSearch
                    v-model="search"
                    type="search"
                    placeholder="Search projects..."
                    :clearable="true"
                    @update:modelValue="handleSearch"
                    class="w-full"
                    :disabled="isLoading"
                  />
                </div>
                <div class="flex space-x-2">
                  <select
                    v-model="projectTypeFilter"
                    @change="handleFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    :disabled="isLoading"
                  >
                    <option value="">All Types</option>
                    <option v-for="type in projectTypes" :key="type" :value="type">
                      {{ formatStatus(type) }}
                    </option>
                  </select>
                  <a :href="'/admin/projects/export/excel'" 
                     class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Export Excel
                  </a>
                  <a :href="'/admin/projects/export/pdf'" 
                     class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Export PDF
                  </a>
                </div>
              </div>
              
              <!-- Projects Table -->
              <div class="overflow-x-auto relative">
                <!-- Loading Overlay -->
                <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
                  <div class="flex flex-col items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="mt-2 text-sm text-gray-600">Loading projects...</span>
                  </div>
                </div>
                
                <div v-else>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Name</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Files</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="project in projectsList.data" :key="project.id" class="hover:bg-gray-50">
                          <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ project.project_name }}</div>
                            <div class="text-sm text-gray-500">{{ project.project_type }}</div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ project.client?.name || 'N/A' }}</div>
                            <div class="text-sm text-gray-500">{{ project.client?.email || 'N/A' }}</div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusBadgeClass(project.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                              {{ formatStatus(project.status) }}
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ project.files_count || 0 }} files
                          </td>
                        
                        </tr>
                        <tr v-if="!isLoading && (!projectsList.data || projectsList.data.length === 0)">
                          <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No projects found.
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  
                  <!-- Pagination -->
                  <div v-if="projectsList.data && projectsList.data.length > 0" class="mt-4 flex items-center justify-between px-6 py-3 bg-white border-t border-gray-200">
                    <div class="flex-1 flex justify-between sm:hidden">
                      <button 
                        @click="fetchPage(projectsList.current_page - 1)" 
                        :disabled="!projectsList.prev_page_url"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                      >
                        Previous
                      </button>
                      <button 
                        @click="fetchPage(projectsList.current_page + 1)" 
                        :disabled="!projectsList.next_page_url"
                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                      >
                        Next
                      </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                      <div>
                        <p class="text-sm text-gray-700">
                          Showing <span class="font-medium">{{ projectsList.from || 0 }}</span>
                          to <span class="font-medium">{{ projectsList.to || 0 }}</span>
                          of <span class="font-medium">{{ projectsList.total || 0 }}</span> results
                        </p>
                      </div>
                      <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                          <button
                            @click="fetchPage(projectsList.current_page - 1)"
                            :disabled="!projectsList.prev_page_url"
                            :class="{'opacity-50 cursor-not-allowed': !projectsList.prev_page_url}"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                          >
                            <span class="sr-only">Previous</span>
                            <!-- Heroicon name: solid/chevron-left -->
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                          </button>
                          <button
                            v-for="page in projectsList.last_page"
                            :key="page"
                            @click="fetchPage(page)"
                            :class="{'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': page === projectsList.current_page, 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !== projectsList.current_page}"
                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                          >
                            {{ page }}
                          </button>
                          <button
                            @click="fetchPage(projectsList.current_page + 1)"
                            :disabled="!projectsList.next_page_url"
                            :class="{'opacity-50 cursor-not-allowed': !projectsList.next_page_url}"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                          >
                            <span class="sr-only">Next</span>
                            <!-- Heroicon name: solid/chevron-right -->
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                          </button>
                        </nav>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseSearch from '@/Components/BaseSearch.vue';

// Debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return function (...args) {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn.apply(this, args), delay);
  };
};

// URL handling methods - client-side only
const updateQueryParams = (params) => {
  if (typeof window === 'undefined') return;
  try {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        searchParams.set(key, value);
      }
    });
    const queryString = searchParams.toString();
    const newUrl = `${window.location.pathname}${queryString ? `?${queryString}` : ''}`;
    if (window.history.replaceState) {
      window.history.replaceState({ ...window.history.state, as: newUrl, url: newUrl }, '', newUrl);
    }
  } catch (error) {
    console.error('Error updating query params:', error);
  }
};

const getQueryParams = () => {
  if (typeof window === 'undefined') return {};
  try {
    return Object.fromEntries(new URLSearchParams(window.location.search));
  } catch (error) {
    console.error('Error getting query params:', error);
    return {};
  }
};

// Props
const props = defineProps({
  projects: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ client_name: '', project_type: '' }) },
  projectTypes: { type: Array, default: () => [] },
  flash: { type: Object, default: () => ({ success: null, step: null }) },
  errors: { type: Object, default: () => ({}) }
});

// Normalize paginator shape for safe template access
const projectsList = computed(() => {
  const p = props.projects || {};
  return {
    data: Array.isArray(p.data) ? p.data : [],
    current_page: p.current_page ?? 1,
    last_page: p.last_page ?? 1,
    total: p.total ?? 0,
    from: p.from ?? 0,
    to: p.to ?? 0,
    next_page_url: p.next_page_url ?? null,
    prev_page_url: p.prev_page_url ?? null,
  };
});

// Reactive state
const search = ref(props.filters?.client_name || '');
const projectTypeFilter = ref(props.filters?.project_type || '');

// Methods
const formatStatus = (status) => {
  if (!status) return 'N/A';
  return status
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const getStatusBadgeClass = (status) => {
  const statusMap = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    default: 'bg-gray-100 text-gray-800',
  };
  return statusMap[status?.toLowerCase()] || statusMap.default;
};

const isLoading = ref(false);
const error = ref(null);

const fetchProjects = async (extra = {}) => {
  try {
    isLoading.value = true;
    error.value = null;
    const params = {
      client_name: search.value?.trim() || undefined,
      project_type:
        projectTypeFilter.value && props.projectTypes.includes(projectTypeFilter.value)
          ? projectTypeFilter.value.trim()
          : undefined,
      ...extra,
    };
    const filteredParams = Object.fromEntries(
      Object.entries(params).filter(([_, v]) => v !== undefined && v !== '')
    );
    const queryString = new URLSearchParams(filteredParams).toString();
    const url = '/admin/projects' + (queryString ? `?${queryString}` : '');
    await router.get(url, {}, {
      preserveState: true,
      replace: true,
      only: ['projects', 'filters'],
      onSuccess: () => updateQueryParams(filteredParams),
      onError: (errors) => {
        error.value = errors?.message || 'Failed to load projects. Please try again.';
        console.error('Error fetching projects:', errors);
      },
    });
  } catch (err) {
    error.value = err?.message || 'An unexpected error occurred. Please try again.';
    console.error('Error in fetchProjects:', err);
  } finally {
    isLoading.value = false;
  }
};

const handleSearch = debounce(() => fetchProjects({ page: 1 }), 500);
const handleFilter = () => fetchProjects({ page: 1 });

const fetchPage = (page) => {
  if (!page || page < 1 || page === projectsList.value.current_page) return;
  fetchProjects({ page });
};

// Initialize filters from URL on component mount
onMounted(() => {
  const params = getQueryParams();
  search.value = params.client_name || '';
  projectTypeFilter.value = params.project_type || '';
  fetchProjects();
});

// Watch for changes in search and filters
watch([search, projectTypeFilter], () => fetchProjects({ page: 1 }));
</script>