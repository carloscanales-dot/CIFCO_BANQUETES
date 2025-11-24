<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
  tickets: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
    }),
  },
});

// --- BÚSQUEDA ---
const search = ref("");

// --- ORDENAMIENTO ---
const sortField = ref("");
const sortDirection = ref("asc");

// --- PAGINACIÓN ---
const page = ref(props.tickets.current_page);
const lastPage = computed(() => props.tickets.last_page);

// Asegurar que siempre sea array
const rows = computed(() =>
  Array.isArray(props.tickets.data) ? props.tickets.data : []
);

// Cargar nueva página
const changePage = (newPage) => {
  router.get(
    route("dashboard.tickets.data"),
    { page: newPage },
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    }
  );
};

// --- FILTRADO ---
const filteredRows = computed(() => {
  const base = rows.value;
  if (!search.value) return base;

  const term = search.value.toLowerCase();

  return base.filter((t) =>
    [
      t.uuid,
      t.product_name,
      t.station_name,
      t.generated_at,
      t.redeem_date,
      t.status, // <-- agregado
    ]
      .join(" ")
      .toLowerCase()
      .includes(term)
  );
});

// --- ORDENAMIENTO ---
const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
  } else {
    sortField.value = field;
    sortDirection.value = "asc";
  }
};

const sortedRows = computed(() => {
  const base = [...filteredRows.value];
  if (!sortField.value) return base;

  return base.sort((a, b) => {
    const valA = a[sortField.value] ?? "";
    const valB = b[sortField.value] ?? "";

    if (valA < valB) return sortDirection.value === "asc" ? -1 : 1;
    if (valA > valB) return sortDirection.value === "asc" ? 1 : -1;
    return 0;
  });
});
</script>

<template>
  <v-card class="mt-6">

    <!-- 🔍 BUSCADOR -->
    <div class="px-4 pt-4">
      <v-text-field v-model="search" label="Buscar..." prepend-inner-icon="mdi-magnify" clearable variant="solo" />
    </div>

    <v-card-text>
      <v-table density="comfortable">
        <thead>
          <tr>
            <th @click="sortBy('uuid')" class="cursor-pointer">
              UUID
              <span v-if="sortField === 'uuid'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>

            <th @click="sortBy('product_name')" class="cursor-pointer">
              Producto
              <span v-if="sortField === 'product_name'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>

            <th @click="sortBy('station_name')" class="cursor-pointer">
              Estación
              <span v-if="sortField === 'station_name'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>

            <th @click="sortBy('generated_at')" class="cursor-pointer">
              Generado
              <span v-if="sortField === 'generated_at'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>

            <th @click="sortBy('redeem_date')" class="cursor-pointer">
              Canjeado
              <span v-if="sortField === 'redeem_date'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>

            <!-- Ordenamiento para status -->
            <th @click="sortBy('status')" class="cursor-pointer">
              Estado
              <span v-if="sortField === 'status'">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="t in sortedRows" :key="t.id">
            <td>{{ t.uuid }}</td>
            <td>{{ t.product_name }}</td>
            <td>{{ t.station_name ?? "-" }}</td>
            <td>{{ new Date(t.generated_at).toLocaleString() }}</td>

            <td>
              <span v-if="t.redeem_date">
                {{ new Date(t.redeem_date).toLocaleString() }}
              </span>
              <span v-else>-</span>
            </td>

            <!-- Estado basado en status -->
            <td>
              <v-chip :color="t.status === 0 ? 'green' : 'grey'" class="text-white" size="small">
                {{ t.status === 0 ? "Canjeado" : "Pendiente" }}
              </v-chip>
            </td>
          </tr>

          <!-- No data -->
          <tr v-if="sortedRows.length === 0">
            <td colspan="6" class="text-center py-6 text-medium-emphasis">
              <v-icon class="mb-1">mdi-alert-circle-outline</v-icon>
              No hay resultados
            </td>
          </tr>
        </tbody>
      </v-table>

      <!-- PAGINACIÓN -->
      <div class="d-flex justify-center mt-5">
        <v-pagination v-model="page" :length="lastPage" :total-visible="7" color="primary" rounded="circle"
          @update:model-value="changePage" />
      </div>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
  user-select: none;
}
</style>
