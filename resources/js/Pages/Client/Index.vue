<script setup>
import { router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  projects: Object,
  filters: Object,
  sort_field: String,
  sort_direction: String
});

const filters = ref({
  client_name: '',
  project_type: '',
  start_date: '',
  end_date: ''
});

// Initialize filters from props
onMounted(() => {
  if (props.filters) {
    filters.value = { ...filters.value, ...props.filters };
  }
});

// Computed properties for export URLs
const excelExportUrl = computed(() => {
  const params = new URLSearchParams();
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });
  return `/admin/projects/export/excel?${params.toString()}`;
});

const pdfExportUrl = computed(() => {
  const params = new URLSearchParams();
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value) params.append(key, value);
  });
  return `/admin/projects/export/pdf?${params.toString()}`;
});

function reload() {
  router.get(route('client.projects.index'), filters.value, {
    preserveState: true,
    replace: true,
  });
}

function sort(key) {
  const direction = props.sort_field === key && props.sort_direction === 'asc' ? 'desc' : 'asc';
  router.get(route('client.projects.index'), {
    ...filters.value,
    sort: key,
    direction
  }, { preserveState: true });
}

function go(url) {
  if (url) router.visit(url, { preserveState: true });
}
</script>

<template>
  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Projects</h1>

    <form class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4" @submit.prevent="reload">
      <input v-model="filters.client_name" class="input" placeholder="Search Client Name" />
      <select v-model="filters.project_type" class="input">
        <option value="">All Types</option>
        <option value="web_app">Web App</option>
        <option value="mobile_app">Mobile App</option>
        <option value="erp">ERP</option>
        <option value="e_commerce">E-commerce</option>
      </select>
      <input v-model="filters.start_date" class="input" type="date" />
      <input v-model="filters.end_date" class="input" type="date" />
      <button class="btn">Filter</button>
    </form>

    <div class="flex gap-2 mb-3">
      <a :href="excelExportUrl" class="btn">Export Excel</a>
      <a :href="pdfExportUrl" class="btn">Export PDF</a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
        <tr class="bg-gray-100">
          <th class="th" @click="sort('project_name')">Project Name</th>
          <th class="th">Client Name</th>
          <th class="th" @click="sort('estimated_budget')">Budget</th>
          <th class="th" @click="sort('start_date')">Start</th>
          <th class="th" @click="sort('end_date')">End</th>
          <th class="th">Files</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="p in projects.data" :key="p.id" class="border-b">
          <td class="td">{{ p.project_name }}</td>
          <td class="td">{{ p.client?.name }}</td>
          <td class="td">{{ p.estimated_budget }}</td>
          <td class="td">{{ p.start_date }}</td>
          <td class="td">{{ p.end_date }}</td>
          <td class="td">{{ p.files_count }}</td>
        </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex items-center gap-2" v-if="projects?.links?.length">
      <button class="btn-secondary" :disabled="!projects.prev_page_url" @click="go(projects.prev_page_url)">Prev</button>
      <span>Page {{ projects.current_page }} / {{ projects.last_page }}</span>
      <button class="btn-secondary" :disabled="!projects.next_page_url" @click="go(projects.next_page_url)">Next</button>
    </div>
  </div>
  
</template>


<style scoped>
@reference '../../../css/app.css';
.input { @apply w-full border rounded px-3 py-2 text-sm; }
.btn { @apply bg-blue-600 text-white px-4 py-2 rounded; }
.btn-secondary { @apply bg-gray-200 text-gray-700 px-3 py-2 rounded; }
.th { @apply text-left p-2 cursor-pointer; }
.td { @apply p-2; }
</style>
