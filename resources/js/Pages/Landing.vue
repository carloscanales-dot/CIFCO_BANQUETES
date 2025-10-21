<template>
    <Head title="Bienvenido" />
    <v-app>
        <v-app-bar color="#303a44">
            <v-toolbar-title>Bienvenido, {{ $page.props.auth.user.name }}</v-toolbar-title>
            <v-spacer></v-spacer>
            <Link href="/logout" method="post" as="button">
                <v-btn>Cerrar Sesión</v-btn>
            </Link>
        </v-app-bar>

        <v-main style="background-image: url('/fondo-lr.jpeg'); background-size: cover;">
            <v-container class="d-flex justify-center align-center fill-height">
                <v-row justify="center">
                    <v-col cols="12" sm="6" md="4">
                        <v-card href="/dashboard" class="mx-auto text-center" width="250" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
                            <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                                <v-icon size="64" class="mb-4">mdi-gift</v-icon>
                                <div class="text-h6">Cortesias</div>
                                <div>Acceder al dashboard de cortesias.</div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <v-col cols="12" sm="6" md="4">
<v-card href="/ventas" class="mx-auto text-center" width="250" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
                            <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                                <v-icon size="64" class="mb-4">mdi-point-of-sale</v-icon>
                                <div class="text-h6">Ventas</div>
                                <div>Acceder a la página de ventas.</div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <v-col cols="12" sm="6" md="4">
                        <v-card href="/creditos" class="mx-auto text-center" width="250" height="250" style="background-color: rgba(255, 255, 255, 0.9);" rounded="lg">
                            <v-card-text class="d-flex flex-column align-center justify-center fill-height">
                                <v-icon size="64" class="mb-4">mdi-account-credit-card</v-icon>
                                <div class="text-h6">Creditos Empleados</div>
                                <div>Acceder a la página de creditos empleados.</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

<script setup>
import { usePage, Link, Head } from '@inertiajs/vue3'

const page = usePage()

// Mostrar info del usuario y roles en la consola
console.log('Usuario actual:', page.props.auth.user)

// Si desde Laravel estás pasando roles en el array de usuario, por ejemplo:
// 'roles' => $user->getRoleNames()
// entonces los verás así:
console.log('Roles del usuario:', page.props.auth.user.roles)
</script>
