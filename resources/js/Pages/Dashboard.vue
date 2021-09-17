<template>
  <app-layout :breadcrumbs="[]" :selected-item="0">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
      </h2>
    </template>

    <v-container class="pb-12 flex-row">
      <div>
        <div class="ml-2 flex justify-items-center items-center align-center align-content-center">
          <v-icon x-large>mdi-domain</v-icon>
          <v-card-title>Struttura Sanitaria</v-card-title>
        </div>
        <v-container class="flex flex-wrap items-center justify-start">
          <v-card class="mr-5 mt-5" width="600">
            <v-img
                class="white--text align-end"
                height="300px"
                lazy-src="/structures_images/structure_lazy.jpg"
                src="/structures_images/structure.jpg">
            </v-img>

            <v-card-title class="pb-0">
              {{ structure.name }}
            </v-card-title>

            <v-card-text class="text--primary mt-4">
              <div><span class="font-weight-bold">Somministrazioni Giornaliere: </span><span>{{ structure.capacity }}</span></div>
              <div><span class="font-weight-bold">Regione: </span><span>{{ structure.region }}</span></div>
              <div><span class="font-weight-bold">Indirizzo: </span><span>{{ structure.address }}</span></div>
              <div><span class="font-weight-bold">Telefono: </span><span>{{ structure.phone_number }}</span></div>
            </v-card-text>

            <v-card-actions>
<!--              <v-btn color="orange" text :href="vaccine.url" target="_blank">-->
<!--                Ulteriori Informazioni-->
<!--              </v-btn>-->
            </v-card-actions>
          </v-card>
        </v-container>
      </div>
      <div>
        <div class="ml-2 flex justify-items-center items-center align-center align-content-center mt-10">
          <v-icon x-large>mdi-needle</v-icon>
          <v-card-title>Vaccini Disponibili</v-card-title>
        </div>
        <v-container class="flex flex-wrap items-center justify-start">
          <v-card v-for="vaccine in vaccinesQty" :key="vaccine.name" class="mr-5 mt-5" width="390">
            <v-img
                class="white--text align-end"
                height="200px"
                :lazy-src="vaccine.lazy_src"
                :src="vaccine.src">
            </v-img>

            <v-card-title class="pb-0">
              {{ vaccine.name }}
            </v-card-title>

            <v-card-text class="text--primary">
              <div><span class="font-weight-bold">Quantità: </span><span>{{ vaccine.qty }}</span></div>
            </v-card-text>

            <v-card-actions>
              <v-btn color="orange" text :href="vaccine.url" target="_blank">
                Ulteriori Informazioni
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-container>
      </div>
    </v-container>
  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout'
import Welcome from '@/Jetstream/Welcome'

export default {
  props: {
    vaccinesQty: Array,
    structure: Object,
  },

  created() {
    document.title = "Dashboard"
  },

  components: {
    AppLayout,
    Welcome,
  },
}
</script>
