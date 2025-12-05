const request = require('supertest');
const path = require('path');
const fs = require('fs');

// Test configuration
const TEST_DB = './test_medicare.db';
const TEST_PORT = 3001;

// Copy original database for testing
if (fs.existsSync(TEST_DB)) {
    fs.unlinkSync(TEST_DB);
}
if (fs.existsSync('./medicare.db')) {
    fs.copyFileSync('./medicare.db', TEST_DB);
}

// Mock environment
process.env.NODE_ENV = 'test';
process.env.PORT = TEST_PORT;

// Import app after setting environment
delete require.cache[require.resolve('./server.js')];
const app = require('./server.js');

// Test data
const testUser = {
    email: 'martin@medic.com',
    password: 'password'
};

let sessionId = null;

describe('Medicare API Tests', () => {
    beforeAll(async () => {
        // Login to get session
        const response = await request(app)
            .post('/api/login')
            .send(testUser);
        
        sessionId = response.headers['x-session-id'];
    });

    afterAll(async () => {
        // Cleanup
        if (fs.existsSync(TEST_DB)) {
            fs.unlinkSync(TEST_DB);
        }
    });

    describe('Authentication', () => {
        test('POST /api/login - successful login', async () => {
            const response = await request(app)
                .post('/api/login')
                .send(testUser)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.user.email).toBe(testUser.email);
            expect(response.body.user.role).toBe('medecin');
        });

        test('POST /api/login - invalid credentials', async () => {
            const response = await request(app)
                .post('/api/login')
                .send({ email: 'invalid@test.com', password: 'wrong' })
                .expect(401);

            expect(response.body.error).toBe('Email ou mot de passe incorrect');
        });

        test('GET /api/user - get current user', async () => {
            const response = await request(app)
                .get('/api/user')
                .set('X-Session-ID', sessionId)
                .expect(200);

            expect(response.body.user.email).toBe(testUser.email);
        });
    });

    describe('Patients API', () => {
        let patientId = null;

        test('POST /api/patients - validation error (empty name)', async () => {
            const response = await request(app)
                .post('/api/patients')
                .set('X-Session-ID', sessionId)
                .send({
                    nom: '',
                    prenom: 'Test',
                    email: 'test@test.com'
                })
                .expect(400);

            expect(response.body.error).toBe('Le nom et le prénom sont obligatoires');
        });

        test('POST /api/patients - validation error (invalid email)', async () => {
            const response = await request(app)
                .post('/api/patients')
                .set('X-Session-ID', sessionId)
                .send({
                    nom: 'Test',
                    prenom: 'Patient',
                    email: 'invalid-email'
                })
                .expect(400);

            expect(response.body.error).toBe('Format d\'email invalide');
        });

        test('POST /api/patients - create patient successfully', async () => {
            const patientData = {
                nom: 'Test',
                prenom: 'Patient',
                email: 'test@example.com',
                telephone: '0123456789',
                date_naissance: '1990-01-01'
            };

            const response = await request(app)
                .post('/api/patients')
                .set('X-Session-ID', sessionId)
                .send(patientData)
                .expect(200);

            expect(response.body.message).toBe('Patient ajouté avec succès');
            expect(response.body.id).toBeDefined();
            patientId = response.body.id;
        });

        test('GET /api/patients - list patients', async () => {
            const response = await request(app)
                .get('/api/patients')
                .set('X-Session-ID', sessionId)
                .expect(200);

            expect(Array.isArray(response.body)).toBe(true);
            expect(response.body.length).toBeGreaterThan(0);
        });

        test('PUT /api/patients/:id - update patient', async () => {
            if (!patientId) return;

            const updateData = {
                nom: 'Updated',
                prenom: 'Patient',
                email: 'updated@example.com'
            };

            const response = await request(app)
                .put(`/api/patients/${patientId}`)
                .set('X-Session-ID', sessionId)
                .send(updateData)
                .expect(200);

            expect(response.body.message).toBe('Patient modifié avec succès');
        });
    });

    describe('Dashboard Stats', () => {
        test('GET /api/dashboard/stats - get statistics', async () => {
            const response = await request(app)
                .get('/api/dashboard/stats')
                .set('X-Session-ID', sessionId)
                .expect(200);

            expect(response.body).toHaveProperty('patients_total');
            expect(response.body).toHaveProperty('medecins_total');
            expect(response.body).toHaveProperty('rdv_aujourd_hui');
            expect(response.body).toHaveProperty('consultations_mois');
        });
    });

    describe('Consultations API', () => {
        test('GET /api/consultations - list consultations', async () => {
            const response = await request(app)
                .get('/api/consultations')
                .set('X-Session-ID', sessionId)
                .expect(200);

            expect(Array.isArray(response.body)).toBe(true);
        });

        test('GET /api/consultations/:id - non-existent consultation', async () => {
            const response = await request(app)
                .get('/api/consultations/99999')
                .set('X-Session-ID', sessionId)
                .expect(404);

            expect(response.body.error).toBe('Consultation non trouvée');
        });

        test('GET /api/consultations/:id - invalid ID', async () => {
            const response = await request(app)
                .get('/api/consultations/invalid')
                .set('X-Session-ID', sessionId)
                .expect(400);

            expect(response.body.error).toBe('ID consultation invalide');
        });
    });

    describe('Error Handling', () => {
        test('Unauthorized access without session', async () => {
            const response = await request(app)
                .get('/api/patients')
                .expect(401);

            expect(response.body.error).toBe('Non connecté');
        });

        test('Invalid session ID', async () => {
            const response = await request(app)
                .get('/api/patients')
                .set('X-Session-ID', 'invalid-session')
                .expect(401);

            expect(response.body.error).toBe('Non connecté');
        });
    });
});