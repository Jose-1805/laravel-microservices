<?php

namespace Jose1805\LaravelMicroservices\Background\src;

interface Resolver
{
    /**
     * Procesa la solicitud y retorna una respuesta para enviar al solicitante
     *
     * @param array $data
     * @return array
     */
    public function handle($data): array;
}
