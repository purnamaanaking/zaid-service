#!/bin/bash
# Zaid Service - Manual API Test Script
# Usage: bash tests/manual-api-test.sh
# Pastikan: php artisan serve sudah jalan di terminal lain

BASE="http://localhost:8000/api/v1"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo ""
echo "========================================="
echo " Zaid Service - Manual API Test"
echo "========================================="
echo ""

# 1. Health
echo -e "${YELLOW}[1] Health Check${NC}"
curl -s "$BASE/health" | python3 -m json.tool
echo ""

# 2. Auth Google (pakai fake token — akan gagal di production, tapi test endpoint responds)
echo -e "${YELLOW}[2] Auth Google (expect 422 tanpa real token)${NC}"
curl -s -X POST "$BASE/auth/google" \
  -H "Content-Type: application/json" \
  -d '{"id_token": "test-token"}' | python3 -m json.tool
echo ""

# 3. Untuk test lengkap, kita bikin token via tinker
echo -e "${YELLOW}[3] Generating test token via artisan...${NC}"
TOKEN=$(cd "$(dirname "$0")/.." && php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'demo@zaid.app')->first();
if (!\$user) { echo 'NO_USER'; exit; }
echo \$user->createToken('test')->plainTextToken;
" 2>/dev/null)

if [ "$TOKEN" = "NO_USER" ] || [ -z "$TOKEN" ]; then
  echo -e "${RED}Demo user not found. Run: php artisan migrate:fresh --seed${NC}"
  exit 1
fi

echo -e "${GREEN}Token: ${TOKEN:0:20}...${NC}"
echo ""

# 4. Me
echo -e "${YELLOW}[4] GET /me${NC}"
curl -s "$BASE/me" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 5. Onboarding status
echo -e "${YELLOW}[5] GET /onboarding/status${NC}"
curl -s "$BASE/onboarding/status" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 6. List tasks
echo -e "${YELLOW}[6] GET /tasks${NC}"
curl -s "$BASE/tasks" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 7. Create task
echo -e "${YELLOW}[7] POST /tasks (create)${NC}"
TASK_RESPONSE=$(curl -s -X POST "$BASE/tasks" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Manual Task",
    "scheduled_date": "2026-05-10",
    "scheduled_time": "15:00:00",
    "description": "Created from manual test script"
  }')
echo "$TASK_RESPONSE" | python3 -m json.tool
TASK_ID=$(echo "$TASK_RESPONSE" | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['task']['id'])" 2>/dev/null)
echo ""

# 8. Get task detail
if [ -n "$TASK_ID" ]; then
  echo -e "${YELLOW}[8] GET /tasks/$TASK_ID${NC}"
  curl -s "$BASE/tasks/$TASK_ID" \
    -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
  echo ""

  # 9. Update task
  echo -e "${YELLOW}[9] PATCH /tasks/$TASK_ID${NC}"
  curl -s -X PATCH "$BASE/tasks/$TASK_ID" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"title": "Updated Manual Task"}' | python3 -m json.tool
  echo ""

  # 10. Complete task
  echo -e "${YELLOW}[10] POST /tasks/$TASK_ID/complete${NC}"
  curl -s -X POST "$BASE/tasks/$TASK_ID/complete" \
    -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
  echo ""

  # 11. Delete task
  echo -e "${YELLOW}[11] DELETE /tasks/$TASK_ID${NC}"
  curl -s -X DELETE "$BASE/tasks/$TASK_ID" \
    -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
  echo ""
fi

# 12. Agenda day
echo -e "${YELLOW}[12] GET /agenda/day${NC}"
TODAY=$(date +%Y-%m-%d)
curl -s "$BASE/agenda/day?date=$TODAY" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 13. Calendar month
echo -e "${YELLOW}[13] GET /calendar/month${NC}"
MONTH=$(date +%Y-%m)
curl -s "$BASE/calendar/month?month=$MONTH" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 14. Prompt (text) — requires real AI API key
echo -e "${YELLOW}[14] POST /prompts (AI text prompt)${NC}"
curl -s -X POST "$BASE/prompts" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"text": "Cek agenda hari ini"}' | python3 -m json.tool
echo ""

# 15. Settings
echo -e "${YELLOW}[15] GET /settings${NC}"
curl -s "$BASE/settings" \
  -H "Authorization: Bearer $TOKEN" | python3 -m json.tool
echo ""

# 16. Update settings
echo -e "${YELLOW}[16] PATCH /settings${NC}"
curl -s -X PATCH "$BASE/settings" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"theme": "dark"}' | python3 -m json.tool
echo ""

# 17. WhatsApp webhook verify
echo -e "${YELLOW}[17] GET /webhooks/whatsapp (verify)${NC}"
curl -s "$BASE/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test&hub_challenge=challenge123"
echo ""
echo ""

# 18. Unauthenticated test
echo -e "${YELLOW}[18] GET /me (no token — expect 401)${NC}"
curl -s "$BASE/me" | python3 -m json.tool
echo ""

# 19. Refresh token
echo -e "${YELLOW}[19] POST /auth/refresh${NC}"
REFRESH_RESPONSE=$(curl -s -X POST "$BASE/auth/refresh" \
  -H "Authorization: Bearer $TOKEN")
echo "$REFRESH_RESPONSE" | python3 -m json.tool
echo ""

echo "========================================="
echo -e "${GREEN} Manual API Test Complete!${NC}"
echo "========================================="
