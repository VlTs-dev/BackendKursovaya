const API_BASE = '/api';
const USER_ID = 1;


const damagedState = {
    name: 'Tarnished',
    level: 40,
    hp: 20,
    maxHp: 1000,
    fp: 5,
    maxFp: 600,
    runes: 3500,
    flasks: 1,
    maxFlasks: 5,
    graceId: 'Gatefront_Ruins',
    region: 'Limgrave'
};


const fullState = {
    ...damagedState,
    hp: damagedState.maxHp,
    fp: damagedState.maxFp,
    flasks: damagedState.maxFlasks
};

let currentState = { ...damagedState };

document.addEventListener('DOMContentLoaded', () => {
    updateCharacterPanel();
    loadSaves();

    const btn = document.getElementById('rest-btn');
    btn.addEventListener('click', async () => {
        await restAtGrace();
    });
});

function updateCharacterPanel() {
    document.getElementById('char-name').textContent = currentState.name;
    document.getElementById('char-location').textContent =
        `${currentState.graceId.replace('_', ' ')} (${currentState.region})`;
    document.getElementById('char-level').textContent = currentState.level;
    document.getElementById('char-hp').textContent =
        `${currentState.hp} / ${currentState.maxHp}`;
    document.getElementById('char-fp').textContent =
        `${currentState.fp} / ${currentState.maxFp}`;
    document.getElementById('char-flasks').textContent =
        `${currentState.flasks} / ${currentState.maxFlasks}`;
    document.getElementById('char-runes').textContent = currentState.runes;
}

async function loadSaves() {
    try {
        const res = await fetch(`${API_BASE}/users/${USER_ID}/saves`);
        if (!res.ok) throw new Error('Ошибка загрузки слотов');

        const saves = await res.json();
        const tbody = document.getElementById('slots-body');
        tbody.innerHTML = '';

        if (!Array.isArray(saves) || saves.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 7;
            td.textContent = 'Пока нет ни одного сохранения.';
            td.style.textAlign = 'center';
            td.style.color = '#9a937a';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        for (const save of saves) {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${save.slot}</td>
                <td>${save.grace_id} (${save.region})</td>
                <td>${save.character_level}</td>
                <td>${save.character_hp}</td>
                <td>${save.character_fp}</td>
                <td>${save.runes}</td>
                <td>${save.version}</td>
                <td>${save.created_at}</td>
                <td>
                    <button class="btn btn-small btn-danger" onclick="deleteSave(${save.id})">
                        Удалить
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        }
    } catch (err) {
        console.error(err);
    }
}

async function restAtGrace() {
    
    currentState = { ...fullState };
    updateCharacterPanel();

    const payload = {
        slot: 1,
        save_type: 'grace',
        character: {
            name: currentState.name,
            level: currentState.level,
            hp: currentState.hp,
            fp: currentState.fp,
            stamina: 0, 
            runes: currentState.runes
        },
        location: {
            grace_id: currentState.graceId,
            region: currentState.region
        },
        flags: {
            boss_Margit_defeated: true
        },
        inventory: [
            { id: 'sword_longsword', upgrade: 3 }
        ]
    };

    
    console.log('Отправляем сейв на сервер:', payload);

    try {
        const res = await fetch(`${API_BASE}/saves`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!res.ok) {
            const text = await res.text();
            console.error('Ошибка создания сейва:', text);
            return;
        }

        const data = await res.json();
        
        console.log('Ответ сервера:', data);

        await loadSaves();
    } catch (err) {
        console.error(err);
    }
}

async function deleteSave(id) {
    if (!confirm(`Удалить сейв #${id}?`)) return;

    try {
        const res = await fetch(`${API_BASE}/saves/${id}`, {
            method: 'DELETE'
        });

        if (!res.ok) {
            const text = await res.text();
            console.error('Ошибка удаления сейва:', text);
            return;
        }

        const data = await res.json();
        console.log('Сейв удалён:', data);

        await loadSaves();
    } catch (err) {
        console.error(err);
    }
}

