<template>

<v-container fluid class="pa-6">


<v-card
class="rounded-xl"
elevation="3"
>


<v-card-text
class="pa-8"
>


<v-row>


<v-col
cols="12"
md="3"
class="text-center"
>


<v-avatar
size="180"
>


<img
v-if="employee.photo"
:src="employee.photo"
/>


<v-icon
v-else
size="100"
>
mdi-account
</v-icon>


</v-avatar>


<h2 class="mt-4">

{{employee.vorname}}
{{employee.nachname}}

</h2>


<v-chip
color="success"
>
Aktiv
</v-chip>


</v-col>







<v-col
cols="12"
md="9"
>


<h2>

<v-icon>
mdi-account-details
</v-icon>

Persönliche Daten

</h2>


<v-divider class="mb-4"/>



<v-row>


<v-col md="6">

<strong>Email:</strong>

{{employee.email}}

</v-col>


<v-col md="6">

<strong>Telefon:</strong>

{{employee.telefon}}

</v-col>


<v-col md="6">

<strong>Abteilung:</strong>

{{employee.abteilung}}

</v-col>


<v-col md="6">

<strong>Position:</strong>

{{employee.position}}

</v-col>


</v-row>






<h2 class="mt-8">

<v-icon>
mdi-home
</v-icon>

Adresse

</h2>



<v-divider/>




<p>

{{employee.adresse?.strasse}}

{{employee.adresse?.hausnummer}}

<br>

{{employee.adresse?.plz}}

{{employee.adresse?.stadt}}

</p>





<v-btn

color="primary"

prepend-icon="mdi-pencil"

@click="$router.push(`/employees/edit/${employee.id}`)"

>

Bearbeiten

</v-btn>




</v-col>


</v-row>



</v-card-text>


</v-card>





</v-container>


</template>








<script setup>


import {ref,onMounted} from 'vue'

import axios from 'axios'

import {useRoute} from 'vue-router'



const route=useRoute()



const employee=ref({})





onMounted(async()=>{


const res=
await axios.get(

`/api/employees_detail.php?id=${route.params.id}`

)


employee.value=res.data


})



</script>