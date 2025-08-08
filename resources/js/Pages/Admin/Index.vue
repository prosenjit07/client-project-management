<template>
  <div class="min-h-screen bg-gray-100">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Project Reports</p>
            
            <div class="mt-8">
              <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium">All Projects</h2>
                <div class="space-x-2">
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
                    <!-- Dynamic rows will be inserted here -->
                    <tr v-if="!projects || projects.length === 0">
                      <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No projects found.
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
import { defineProps, ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';

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
    default: () => ({})
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
</script>