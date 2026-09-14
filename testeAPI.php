curl -X POST http://localhost:8000/api/frete \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "cep_destino": "04565001",
    "peso": 1,
    "altura": 10,
    "largura": 10,
    "comprimento": 10
  }'
[{"id":1,"name":"PAC","price":21.15,"discount":"8.65","currency":"R$","delivery_time":2,"delivery_range":{"min":2,"max":2},"packages":[{"price":21.15,"discount":"8.65","format":"box","dimensions":{"height":"10","width":"16","length":"24"},"weight":"1","insurance_value":0}],"additional_services":{"receipt":false,"own_hand":false},"company":{"id":1,"name":"Correios","picture":"https:\/\/storage.googleapis.com\/sandbox-api-superfrete.appspot.com\/logos\/correios.png"},"has_error":false},{"id":2,"name":"SEDEX","price":25.25,"discount":"8.75","currency":"R$","delivery_time":1,"delivery_range":{"min":1,"max":1},"packages":[{"price":25.25,"discount":"8.75","format":"box","dimensions":{"height":"10","width":"16","length":"24"},"weight":"1","insurance_value":0}],"additional_services":{"receipt":false,"own_hand":false},"company":{"id":1,"name":"Correios","picture":"https:\/\/storage.googleapis.com\/sandbox-api-superfrete.appspot.com\/logos\/correios.png"},"has_error":false}]