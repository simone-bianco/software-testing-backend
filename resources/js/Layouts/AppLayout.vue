<template>
  <v-app>
    <div class="fixed flex w-full z-20">
      <v-app-bar-nav-icon :dark="drawer" class="mt-6 ml-3" @click="drawer = !drawer" />
    </div>
    <div class="fixed flex w-full profile-index">
<!--      <v-overlay />-->
        <v-list-item class="px-2 justify-end mt-3 mr-10">
          <div class="flex justify-end items-center justify-items-center text-center mr-20 mt-2">
<!--            <v-img class="w-12"-->
<!--                   src="https://w7.pngwing.com/pngs/81/570/png-transparent-profile-logo-computer-icons-user-user-blue-heroes-logo-thumbnail.png">-->
<!--            </v-img>-->
            <span class="text-sm mr-1">{{ $page.props.responsible.name }}</span>
            <div>
              <v-icon x-large>mdi-account-circle</v-icon>
            </div>
          </div>
        </v-list-item>
    </div>
    <v-app-bar color="white accent-4 app-bar-index" :height="80" app dense light prominent>
      <v-spacer></v-spacer>
    </v-app-bar>
    <v-navigation-drawer
        v-model="drawer"
        app
        class="accent-4"
        dark
        :value="isScreenSmall && drawer"
        :width="285">
      <div class="flex justify-center items-center h-20">
        <v-list-item>
          <v-list-item-content class="ml-11">
            <v-list-item-title class="title">
              Gestione Prenotazioni
            </v-list-item-title>
            <v-list-item-subtitle>
              Pannello Operatore Sanitario
            </v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </div>

      <v-divider></v-divider>
      <v-list>
        <v-list-item-group
            v-model="selectedItem">
          <v-list-item
              @click="submit(item)"
              v-for="(item, i) in items"
              :key="i"
              link>
            <v-list-item-icon>
              <v-icon>{{ item.icon }}</v-icon>
            </v-list-item-icon>

            <v-list-item-content>
              <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-item-group>
      </v-list>

      <template v-slot:append>
        <div class="pa-2">
          <v-btn @click="logout" block>
            Logout
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!--    <v-app-bar app>-->
    <!-- -->
    <!--    </v-app-bar>-->

    <!-- Sizes your content based upon application components -->
    <v-main>

      <!-- Provides the application the proper gutter -->
      <v-container fluid :class="mainContainerClass">
        <v-breadcrumbs class="ml-5" :items="breadItems" v-if="breadItems.length > 0">
          <template v-slot:divider>
            <v-icon>mdi-chevron-right</v-icon>
          </template>
        </v-breadcrumbs>
        <slot></slot>

        <!-- If using vue-router -->
        <!--        <router-view></router-view>-->
      </v-container>
    </v-main>
    <v-snackbars :messages.sync="errorMessages" :timeout="5000" bottom right color="error" />
    <v-snackbars :messages.sync="successMessages" :timeout="5000" bottom right color="success" />
    <v-snackbars :messages.sync="controllerErrors" :timeout="5000" bottom right color="error" />
    <v-snackbars :messages.sync="controllerSuccess" :timeout="5000" bottom right color="success" />
  </v-app>
</template>

<script>
export default {
  props: {
    externalErrors: Array,
    externalSuccess: Array,
    breadcrumbs: Array,
    selectedItem: Number,
  },

  mounted() {
    let success = this.$page.props.success
    if (success !== null && success !== '' && success !== undefined) {
      this.controllerSuccess.push(success)
    }

    let error = this.$page.props.error
    if (error !== null && error !== '' && error !== undefined) {
      this.controllerErrors.push(error)
    }

    window.onresize = () => {
      this.windowWidth = window.innerWidth
    }
  },

  computed: {
    isScreenSmall() {
      return this.windowWidth <= 1151
    },

    breadItems() {
      return this.breadcrumbs !== null && this.breadcrumbs ? this.breadcrumbs : []
    },

    mainContainerClass() {
      console.log(this.breadItems.length)
      if (this.breadItems.length > 0) {
        return 'mt-3'
      }
      return ''
    }
  },

  watch: {
    'windowWidth': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        if (newValue !== oldValue) {
          this.drawer = !this.isScreenSmall;
        }
      }
    },
  },

  data() {
    return {
      errorMessages: this.externalErrors,
      successMessages: this.externalSuccess,
      controllerSuccess: [],
      controllerErrors: [],
      windowWidth: window.innerWidth,
      drawer: !this.isScreenSmall,
      items: [
        { title: 'Dashboard', icon: 'mdi-view-dashboard', route: 'dashboard.index', method: 'get' },
        { title: 'Visualizza Prenotazioni', icon: 'mdi-book-variant', route: 'reservations.index', method: 'get' },
        // { title: 'Registra Responsabile', icon: 'mdi-book-variant', route: 'responsible.create', method: 'get' },
      ],
      showingNavigationDropdown: false,
    }
  },

  methods: {
    submit(item) {
      if (item.method === 'get') {
        this.$inertia.get(route(item.route));
      } else {
        this.$inertia.post(route(item.route));
      }
    },

    logout() {
      this.$inertia.post(route('logout'));
    },
  }
}
</script>

<style lang="scss" scoped>
.profile-index {
  z-index: 5;
}

.app-bar-index {
  z-index: 3;
}

.theme--dark.v-navigation-drawer {
  background-color: #212529;
}
</style>