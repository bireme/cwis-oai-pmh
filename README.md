cwis-oai-pmh
============

Fork feito para contemplar  corretamente o campo Decs.

No arquivo __OAIServer.php__, na __linha 761__, foi adicionado o seguinte trecho:

```
if($LocalFieldName == 987654)
  $Content = explode(" , ", $Content);
```

Ao verificar que é um campo Decs (id: 987654), é realizada a explosão no separador, transformando a String em um Array.

O plugin, ao identificar que é um Array, age duplicando a tag.
