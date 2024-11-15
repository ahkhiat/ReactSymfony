import { useState } from 'react';
import axios from 'axios';

function Login() {
    const [step, setStep] = useState(1); // Étape 1 : Entrer l'email, Étape 2 : Entrer le code
    const [email, setEmail] = useState('');
    const [code, setCode] = useState('');
    const [error, setError] = useState('');

    const handleSendCode = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post('https://localhost:8000/auth/send-code', { email });
            if (response.status === 200) {
                setStep(2);
            }
        } catch (err) {
            setError('Une erreur est survenue lors de l’envoi du code.');
        }
    };

    const handleVerifyCode = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post('https://localhost:8000/auth/verify-code', { email, code }, {withCredentials: true});
            if (response.status === 200) {
                alert('Connexion réussie !');
                // Rediriger l'utilisateur ou effectuer des actions supplémentaires après la connexion réussie
            }
        } catch (err) {
            setError('Code incorrect.');
        }
    };

    return (
        <div>
            {step === 1 && (
                <form onSubmit={handleSendCode}>
                    <h2>Connexion</h2>
                    {error && <p style={{ color: 'red' }}>{error}</p>}
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="Entrez votre email"
                        required
                    />
                    <button type="submit">Recevoir un code</button>
                </form>
            )}
            {step === 2 && (
                <form onSubmit={handleVerifyCode}>
                    <h2>Vérifiez votre code</h2>
                    {error && <p style={{ color: 'red' }}>{error}</p>}
                    <input
                        type="text"
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        placeholder="Entrez le code reçu"
                        required
                    />
                    <button type="submit">Se connecter</button>
                </form>
            )}
        </div>
    );
}

export default Login;
