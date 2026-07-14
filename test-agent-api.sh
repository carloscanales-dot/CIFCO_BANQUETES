# Script de prueba para la API del Agente de Impresión
# Requiere: curl, jq (opcional para formatear JSON)

BASE_URL="https://bistro.cifco.gob.sv"
AGENT_ID="LOCAL_AGENT_001"
AGENT_SECRET="secret123"

echo "========================================="
echo "  Test de API del Agente de Impresión"
echo "========================================="
echo ""

# 1. Login
echo "1. Autenticando agente..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/agent/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"agent_id\": \"$AGENT_ID\", \"agent_secret\": \"$AGENT_SECRET\"}")

echo "Respuesta: $LOGIN_RESPONSE"
echo ""

# Extraer token (requiere jq, o puedes hacerlo manualmente)
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')

if [ -z "$TOKEN" ]; then
    echo "❌ Error: No se pudo obtener el token"
    exit 1
fi

echo "✅ Token obtenido: $TOKEN"
echo ""

# 2. Obtener trabajos pendientes
echo "2. Obteniendo trabajos pendientes..."
JOBS_RESPONSE=$(curl -s -X GET "$BASE_URL/api/agent/print-jobs/pending" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Respuesta: $JOBS_RESPONSE"
echo ""

# Si hay trabajos, marca el primero como impreso (para prueba)
# Nota: Descomenta las siguientes líneas solo si quieres marcar jobs automáticamente
# JOB_ID=$(echo $JOBS_RESPONSE | grep -o '"id":[0-9]*' | head -1 | sed 's/"id"://')
# if [ ! -z "$JOB_ID" ]; then
#     echo "3. Marcando job $JOB_ID como impreso..."
#     PRINT_RESPONSE=$(curl -s -X POST "$BASE_URL/api/agent/print-jobs/$JOB_ID/printed" \
#       -H "Authorization: Bearer $TOKEN" \
#       -H "Accept: application/json")
#     echo "Respuesta: $PRINT_RESPONSE"
# fi

echo "========================================="
echo "  Pruebas completadas"
echo "========================================="
