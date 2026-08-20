# Shifr

## Capabilities

- compute hash для разрешённых algorithms;
- text transforms;
- IOC extraction;
- JWT structure inspection;
- classic ciphers: Caesar, Atbash, ROT13/ROT47 и дополнительные processors для Vigenere/XOR/affine/Playfair/rail fence/transposition согласно request/action routing.

## Architecture

`ShifrController` принимает специализированные Form Requests и вызывает `ShifrToolkitService` либо `ShifrClassicCipherService`. Services делегируют узким Actions, результаты возвращаются DTO. External APIs, background Parser и export infrastructure не используются.

Frontend: tabs, shared form/result components и `useShifrRequest` в `resources/js/pages/shifr`.

## Routes

`/shifr/hash`, `/transform`, `/ioc-extract`, `/jwt-inspect`, `/classic`; все throttled `90/min`, находятся в authenticated+verified group и не входят в paid resource map.

## Security и limitations

JWT inspection декодирует структуру, но не подтверждает доверие к token без явной cryptographic verification contract. Classic ciphers — educational/analysis tools, не современная криптография. Не передавайте production secrets через UI; учитывайте request/activity logging policy и browser history.
