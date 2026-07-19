<template>

<v-navigation-drawer

  :rail="uiStore.rail"

  permanent

  color="grey-darken-4"

  class="elevation-3"

  :width="uiStore.rail ? 56 : 280"

>


<!-- HEADER -->

<div
class="sidebar-header"
:class="{ 'rail-mode': uiStore.rail }"
>


<div
class="d-flex align-center"
:class="
uiStore.rail
? 'justify-center'
: 'justify-space-between'
"
>


<div class="d-flex align-center">


<v-icon
size="32"
color="white"
>
mdi-database
</v-icon>



<span
v-if="!uiStore.rail"
class="text-h5 font-weight-bold text-white ml-2"
>
StockManager
</span>


</div>




<v-btn

v-if="!uiStore.rail"

variant="text"

icon="mdi-chevron-left"

size="small"

color="white"

@click.stop="uiStore.toggleRail"

/>



</div>


</div>





<v-divider class="my-2"/>




<v-list
nav
dense
>


<!-- DASHBOARD -->

<v-list-item

prepend-icon="mdi-view-dashboard-outline"

title="Dashboard"

:active="activeTab==='dashboard'"

@click="navigateTo('dashboard')"

/>





<!-- TICKETS -->

<v-list-item

prepend-icon="mdi-ticket-outline"

title="Tickets"

:active="activeTab==='tickets'"

@click="navigateTo('tickets')"

/>







<!-- PRODUKTE -->

<v-list-group
value="products"
>


<template #activator="{props}">


<v-list-item

v-bind="props"

title="Produkte"

prepend-icon="mdi-package-variant"

>


</v-list-item>


</template>




<v-list-item

prepend-icon="mdi-format-list-bulleted"

title="Alle Produkte"

@click="navigateTo('products-list')"

/>



<v-list-item

prepend-icon="mdi-plus-box-outline"

title="Neues Produkt"

@click="navigateTo('add-product')"

/>



<v-list-item

prepend-icon="mdi-tag-outline"

title="Kategorien"

@click="navigateTo('categories')"

/>


</v-list-group>








<!-- KUNDEN -->

<v-list-group
value="customers"
>


<template #activator="{props}">


<v-list-item

v-bind="props"

title="Kunden"

prepend-icon="mdi-account-group-outline"

/>


</template>



<v-list-item

prepend-icon="mdi-format-list-bulleted"

title="Alle Kunden"

@click="navigateTo('customers-list')"

/>



<v-list-item

prepend-icon="mdi-account-plus-outline"

title="Neuer Kunde"

@click="navigateTo('add-customer')"

/>



</v-list-group>








<!-- MITARBEITER SAP FIORI -->

<v-list-group

value="employees"

>


<template #activator="{props}">


<v-list-item

v-bind="props"

title="Mitarbeiter"

prepend-icon="mdi-account-tie-outline"

/>


</template>





<v-list-item

prepend-icon="mdi-account-multiple-outline"

title="Mitarbeiter Übersicht"

@click="navigateTo('employees-list')"

>


<template #append>


<v-chip

size="x-small"

color="primary"

>

NEW

</v-chip>


</template>


</v-list-item>





<v-list-item

prepend-icon="mdi-account-plus-outline"

title="Neuer Mitarbeiter"

@click="navigateTo('add-employee')"

/>





<v-list-item

prepend-icon="mdi-card-account-details-outline"

title="Profile"

@click="navigateTo('employee-profile')"

/>



</v-list-group>










<!-- BESTELLUNGEN -->


<v-list-group

value="orders"

>


<template #activator="{props}">


<v-list-item

v-bind="props"

title="Bestellungen"

prepend-icon="mdi-cart-outline"

/>


</template>




<v-list-item

prepend-icon="mdi-format-list-bulleted"

title="Alle Bestellungen"

@click="navigateTo('orders-list')"

/>




<v-list-item

prepend-icon="mdi-cart-plus"

title="Neue Bestellung"

@click="navigateTo('add-order')"

/>



</v-list-group>








<v-divider class="my-2"/>




<v-list-item

prepend-icon="mdi-chart-line"

title="Statistiken"

@click="navigateTo('stats')"

/>





<v-list-item

prepend-icon="mdi-help-circle-outline"

title="Hilfe"

@click="navigateTo('help')"

/>





</v-list>






</v-navigation-drawer>


</template>






<script setup>


import { computed } from 'vue'

import { useRouter, useRoute } from 'vue-router'

import { useUiStore } from '../../stores/uiStore'



const router = useRouter()

const route = useRoute()


const uiStore = useUiStore()





// ACTIVE MENU AUTOMATISCH

const activeTab = computed(()=>{


if(route.path.startsWith('/employees'))

return 'employees-list'



if(route.path.startsWith('/products'))

return 'products-list'



if(route.path.startsWith('/tickets'))

return 'tickets'



if(route.path.startsWith('/customers'))

return 'customers-list'



return 'dashboard'


})








const navigateTo = (tab)=>{


const routes={


dashboard:'/',


tickets:'/tickets',



'products-list':'/products',


'add-product':'/products/add',


categories:'/products/categories',



'customers-list':'/customers',


'add-customer':'/customers/add',




// MITARBEITER

'employees-list':'/employees',


'add-employee':'/employees/add',


'employee-profile':'/employees/1',




'orders-list':'/orders',


'add-order':'/orders/add',



stats:'/stats',


help:'/help'


}





router.push(routes[tab] || '/')


}




</script>







<style scoped>


.sidebar-header {


padding:20px 16px;


background:
rgba(0,0,0,.25);


border-bottom:
1px solid rgba(255,255,255,.1);


}



.sidebar-header.rail-mode {


padding:20px 8px;


}





.v-navigation-drawer {


border-right:
1px solid rgba(0,0,0,.08);


}





.v-list-item {


min-height:
38px !important;


}





.v-list-item--active {


background:
rgba(25,118,210,.15);


color:#1976d2;


font-weight:600;


}





.v-list-item--active .v-icon {


color:#1976d2;


}




.v-list-group__items
.v-list-item {


padding-left:
32px !important;


}




</style>