<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initialscale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>Vorlage</title>
<style>
table {
border-collapse: collapse;
width: 100%;
border: 5px solid #ddd;
}
thead {
font-style:italic;
background-color: orange
}
th, td {
text-align: left;
padding: 16px;
}
tr:nth-child(even) {
background-color: #FAC898;
}

</style>
</head>
<body>
<h1>Vermieter</h1>
<h2>Übersicht der Vermieter </h2>
<table>
<thead>
<tr>
<th>Bezeichnung</th>
<th>Kontakt</th>
<th>Telefon</th>
<th>Email</th>

</tr>
</thead>
<tbody>
@foreach ( $suppliers as $supplier )
<tr>

<td>{{$supplier->bezeichnung}}</td>
<td>{{$supplier->kontakt}}</td>
<td>{{$supplier->phone}}</td>
<td>{{$supplier->email}}</td>

</tr>
@endforeach
</tbody>
</table>
</body>
</html>