<template>
  <div class="min-h-screen bg-gray-100">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Project Reports</p>
            
            <div class="mt-8">
              <div class="flex flex-col space-y-4 sm:flex-row sm:justify-between sm:items-center mb-6">
                <div class="w-full sm:w-1/3">
                  <BaseSearch
                    v-model="search"
                    type="search"
                    placeholder="Search projects..."
                    :clearable="true"
                    @update:modelValue="handleSearch"
                    class="w-full"
                  />
                </div>
                <div class="flex space-x-2">
                  <select
                    v-model="statusFilter"
                    @change="handleFilter"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                  <a :href="route('admin.projects.export.excel')" 
                     class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Export Excel
                  </a>
                  <a :href="route('admin.projects.export.pdf')" 
                     class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Export PDF
                  </a>
                </div>
              </div>
              
              <!-- Projects Table -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Project
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Client
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <template v-if="filteredProjects && filteredProjects.length > 0">
                      <tr v-for="project in filteredProjects" :key="project.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900">{{ project.project_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900">{{ project.client?.name || 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span :class="getStatusBadgeClass(project.project_type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                            {{ formatStatus(project.project_type) }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <Link :href="route('admin.projects.show', project.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">View</Link>
                        </td>
                      </tr>
                    </template>
                    <tr v-else>
                      <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                          <h3 class="text-lg font-medium text-gray-900 mb-1">No projects found</h3>
                          <p class="text-gray-500">
                            <template v-if="search || statusFilter">
                              No projects match your search criteria. Try adjusting your filters.
                            </template>
                            <template v-else>
                              There are no projects to display at the moment.
                            </template>
                          </p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, ref, computed, onMounted, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import BaseSearch from '@/Components/BaseSearch.vue';

// Debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return function(...args) {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }
    timeoutId = setTimeout(() => {
      fn.apply(this, args);
    }, delay);
  };
};

// Get page props
const page = usePage();

// URL handling methods - client-side only
const updateQueryParams = (params) => {
  if (typeof window === 'undefined') return;
  
  try {
    const searchParams = new URLSearchParams(window.location.search);
    
    // Update the search params with new values
    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === '') {
        searchParams.delete(key);
      } else {
        searchParams.set(key, value);
      }
    });

    // Update the URL without reloading the page
    const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
    router.visit(newUrl, { preserveState: true, replace: true });
  } catch (error) {
    console.error('Error updating query params:', error);
  }
};

// Parse current URL params - client-side only
const getQueryParams = () => {
  if (typeof window === 'undefined') return {};
  
  try {
    return Object.fromEntries(new URLSearchParams(window.location.search));
  } catch (error) {
    console.error('Error getting query params:', error);
    return {};
  }
};

// Define props with proper typing
const props = defineProps({
  projects: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      status: ''
    })
  },
  flash: {
    type: Object,
    default: () => ({ success: null, error: null })
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

// Reactive state
const search = ref(props.filters.client_name || '');
const statusFilter = ref(props.filters.project_type || '');

// Computed property for filtered projects
const filteredProjects = computed(() => {
  if (!props.projects) return [];
  return props.projects;
});

// Methods
const formatStatus = (status) => {
  if (!status) return 'N/A';
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const getStatusBadgeClass = (status) => {
  const statusMap = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800',
    'default': 'bg-gray-100 text-gray-800'
  };
  
  return statusMap[status?.toLowerCase()] || statusMap.default;
};

const handleSearch = debounce(() => {
  fetchProjects();
}, 500);

const handleFilter = () => {
  fetchProjects();
};

// Initialize filters from URL on component mount
onMounted(() => {
  const params = getQueryParams();
  search.value = params.client_name || '';
  statusFilter.value = params.project_type || '';
  
  // Ensure we have the latest data when component mounts
  fetchProjects();
});

// Watch for changes in the URL and update filters
watch(() => usePage().url, () => {
  const params = getQueryParams();
  search.value = params.client_name || '';
  statusFilter.value = params.project_type || '';
}, { immediate: true });

// Watch for changes in search and filters
watch([search, statusFilter], () => {
  fetchProjects();
}, { deep: true });

// Function to fetch projects with current filters
const fetchProjects = () => {
  const params = {
    client_name: search.value,
    project_type: statusFilter.value
  };
  
  // Only include non-empty parameters
  const filteredParams = Object.fromEntries(
    Object.entries(params).filter(([_, v]) => v !== '')
  );
  
  // Use Inertia's router to get a fresh set of data with the current filters
  router.get(route('admin.projects.index'), filteredParams, {
    preserveState: true,
    replace: true,
    only: ['projects', 'filters']
  });
};
</script>